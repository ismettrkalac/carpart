<?php

namespace Tests\Unit;

use App\Services\Vpic\DecodedVehicle;
use App\Services\Vpic\VinDecoderService;
use App\Services\Vpic\VpicLookupException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Exercises VinDecoderService directly (mocked HTTP), separate from the
 * route-level assertions in Tests\Feature\VinLookupTest.
 */
class VinDecoderServiceTest extends TestCase
{
    private const VIN = '1HGCM82633A004352';

    public function test_it_returns_a_fully_decoded_vehicle_on_a_clean_decode(): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response($this->vpicFixture([
                'ErrorCode' => '0',
                'ErrorText' => '0 - VIN decoded clean. Check Digit (9th position) is correct',
            ])),
        ]);

        $vehicle = app(VinDecoderService::class)->decodeVin(self::VIN);

        $this->assertInstanceOf(DecodedVehicle::class, $vehicle);
        $this->assertTrue($vehicle->isFullyDecoded());
        $this->assertFalse($vehicle->hasNoData());
        $this->assertSame('HONDA', $vehicle->make);
        $this->assertSame('Accord', $vehicle->model);
        $this->assertSame(2003, $vehicle->modelYear);
    }

    public function test_it_returns_partial_data_and_flags_it_when_the_decode_is_incomplete(): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response($this->vpicFixture([
                'ErrorCode' => '1',
                'ErrorText' => '1 - Check Digit (9th position) does not calculate properly',
            ])),
        ]);

        $vehicle = app(VinDecoderService::class)->decodeVin(self::VIN);

        $this->assertFalse($vehicle->isFullyDecoded());
        $this->assertFalse($vehicle->hasNoData());
        $this->assertSame('HONDA', $vehicle->make);
        $this->assertStringContainsString('Check Digit', $vehicle->errorText);
    }

    public function test_it_treats_blank_fields_as_null_and_reports_no_data(): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response($this->vpicFixture([
                'ErrorCode' => '7',
                'Make' => '',
                'Model' => '',
                'ModelYear' => '',
                'EngineCylinders' => '',
                'DisplacementL' => '',
                'FuelTypePrimary' => '',
            ])),
        ]);

        $vehicle = app(VinDecoderService::class)->decodeVin('11111111111111111');

        $this->assertTrue($vehicle->hasNoData());
        $this->assertNull($vehicle->make);
        $this->assertNull($vehicle->modelYear);
        $this->assertSame(
            ['Make' => 'Unknown', 'Model' => 'Unknown', 'Model year' => 'Unknown', 'Engine cylinders' => 'Unknown', 'Displacement (L)' => 'Unknown', 'Fuel type' => 'Unknown'],
            $vehicle->displayFields()
        );
    }

    public function test_it_throws_when_the_api_responds_with_a_server_error(): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response('Internal Server Error', 500),
        ]);

        $this->expectException(VpicLookupException::class);

        app(VinDecoderService::class)->decodeVin(self::VIN);
    }

    public function test_it_throws_when_the_connection_fails(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('Connection timed out');
        });

        $this->expectException(VpicLookupException::class);

        app(VinDecoderService::class)->decodeVin(self::VIN);
    }

    public function test_it_caches_a_decoded_vin_so_the_api_is_only_called_once(): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response($this->vpicFixture()),
        ]);

        $service = app(VinDecoderService::class);
        $first = $service->decodeVin(self::VIN);
        $second = $service->decodeVin(self::VIN);

        Http::assertSentCount(1);
        $this->assertEquals($first, $second);
    }

    public function test_it_caches_different_model_years_separately(): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response($this->vpicFixture()),
        ]);

        $service = app(VinDecoderService::class);
        $service->decodeVin(self::VIN, 2003);
        $service->decodeVin(self::VIN, 2004);

        Http::assertSentCount(2);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function vpicFixture(array $overrides = []): array
    {
        $result = array_merge([
            'ErrorCode' => '0',
            'ErrorText' => '0 - VIN decoded clean. Check Digit (9th position) is correct',
            'AdditionalErrorText' => '',
            'Make' => 'HONDA',
            'Model' => 'Accord',
            'ModelYear' => '2003',
            'EngineCylinders' => '6',
            'DisplacementL' => '2.998832712',
            'FuelTypePrimary' => 'Gasoline',
            'FuelTypeSecondary' => '',
        ], $overrides);

        return [
            'Count' => 1,
            'Message' => 'Results returned successfully.',
            'SearchCriteria' => 'VIN(s): '.self::VIN,
            'Results' => [$result],
        ];
    }
}
