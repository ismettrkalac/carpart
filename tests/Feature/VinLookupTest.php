<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VinLookupTest extends TestCase
{
    private const VIN = '1HGCM82633A004352';

    public function test_it_shows_an_empty_form_when_no_vin_is_supplied(): void
    {
        Http::fake();

        $response = $this->get('/vin-lookup');

        $response->assertOk();
        $response->assertSee('VIN Lookup');
        $response->assertDontSee('Results for');
        Http::assertNothingSent();
    }

    public function test_it_rejects_a_malformed_vin_without_calling_the_api(): void
    {
        Http::fake();

        $response = $this->get('/vin-lookup?vin=SHORT');

        $response->assertStatus(422);
        $response->assertSee('A VIN is exactly 17 characters.');
        Http::assertNothingSent();
    }

    public function test_it_rejects_a_vin_containing_disallowed_letters(): void
    {
        Http::fake();

        // VINs never contain I, O, or Q.
        $response = $this->get('/vin-lookup?vin=1O1CM82633A00435I');

        $response->assertStatus(422);
        $response->assertSee('valid VIN');
        Http::assertNothingSent();
    }

    public function test_it_decodes_a_fully_recognized_vin(): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response($this->vpicFixture([
                'ErrorCode' => '0',
                'ErrorText' => '0 - VIN decoded clean. Check Digit (9th position) is correct',
                'Make' => 'HONDA',
                'Model' => 'Accord',
                'ModelYear' => '2003',
                'EngineCylinders' => '6',
                'DisplacementL' => '2.998832712',
                'FuelTypePrimary' => 'Gasoline',
            ])),
        ]);

        $response = $this->get('/vin-lookup?vin='.self::VIN);

        $response->assertOk();
        $response->assertSee('Results for '.self::VIN);
        $response->assertSee('HONDA');
        $response->assertSee('Accord');
        $response->assertSee('2003');
        $response->assertSee('Gasoline');
        $response->assertDontSee('Decoded with warnings');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/vehicles/DecodeVinValues/'.self::VIN)
            && $request['format'] === 'json'
        );
    }

    public function test_it_passes_the_model_year_through_to_the_api_when_supplied(): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response($this->vpicFixture(['ModelYear' => '2003'])),
        ]);

        $this->get('/vin-lookup?vin='.self::VIN.'&modelyear=2003')->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'modelyear=2003'));
    }

    public function test_it_flags_an_incomplete_decode_but_still_shows_the_partial_data(): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response($this->vpicFixture([
                'ErrorCode' => '1',
                'ErrorText' => '1 - Check Digit (9th position) does not calculate properly',
                'Make' => 'HONDA',
                'Model' => 'Accord',
                'ModelYear' => '2003',
            ])),
        ]);

        $response = $this->get('/vin-lookup?vin='.self::VIN);

        $response->assertOk();
        $response->assertSee('Decoded with warnings');
        $response->assertSee('Check Digit (9th position) does not calculate properly');
        // Partial data is still shown, not hidden because of the warning.
        $response->assertSee('HONDA');
        $response->assertSee('Accord');
    }

    public function test_it_treats_a_vin_with_no_decodable_data_as_unknown(): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response($this->vpicFixture([
                'ErrorCode' => '7',
                'ErrorText' => '7 - Manufacturer is not registered with NHTSA for sale or importation in the U.S.',
                'Make' => '',
                'Model' => '',
                'ModelYear' => '',
                'EngineCylinders' => '',
                'DisplacementL' => '',
                'FuelTypePrimary' => '',
            ])),
        ]);

        $response = $this->get('/vin-lookup?vin=11111111111111111');

        $response->assertOk();
        $response->assertSee('could not identify this VIN');
        $response->assertDontSee('Unknown Unknown');
    }

    public function test_it_shows_a_friendly_message_when_the_api_returns_a_server_error(): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response('Internal Server Error', 500),
        ]);

        $response = $this->get('/vin-lookup?vin='.self::VIN);

        $response->assertOk();
        $response->assertSee('vehicle lookup service returned an error');
        $response->assertDontSee('Results for');
    }

    public function test_it_shows_a_friendly_message_when_the_api_is_unreachable(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('Connection timed out');
        });

        $response = $this->get('/vin-lookup?vin='.self::VIN);

        $response->assertOk();
        $response->assertSee('Could not reach the vehicle lookup service');
        $response->assertDontSee('Results for');
    }

    public function test_it_caches_a_successful_lookup_and_does_not_call_the_api_twice(): void
    {
        Http::fake([
            'vpic.nhtsa.dot.gov/*' => Http::response($this->vpicFixture()),
        ]);

        $this->get('/vin-lookup?vin='.self::VIN)->assertOk();
        $this->get('/vin-lookup?vin='.self::VIN)->assertOk();

        Http::assertSentCount(1);
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
