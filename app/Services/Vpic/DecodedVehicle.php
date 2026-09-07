<?php

namespace App\Services\Vpic;

/**
 * A normalized view of the NHTSA vPIC "DecodeVinValues" result for a single VIN.
 *
 * NHTSA vPIC only identifies a vehicle (make/model/year/engine/fuel); it has
 * no knowledge of parts, prices, stock, or verified part compatibility.
 */
final readonly class DecodedVehicle
{
    /**
     * @param  array<int, string>  $additionalErrorMessages
     */
    public function __construct(
        public string $vin,
        public ?string $make,
        public ?string $model,
        public ?int $modelYear,
        public ?string $engineCylinders,
        public ?string $displacementL,
        public ?string $fuelTypePrimary,
        public ?string $fuelTypeSecondary,
        public string $errorCode,
        public string $errorText,
        public array $additionalErrorMessages,
    ) {}

    /**
     * Build an instance from one entry of the vPIC API's "Results" array.
     *
     * @param  array<string, mixed>  $result
     */
    public static function fromApiResult(string $vin, array $result): self
    {
        $errorCode = trim((string) ($result['ErrorCode'] ?? ''));

        // vPIC can return a comma-separated list of codes, e.g. "1,6".
        $additionalErrorMessages = array_values(array_filter(array_map(
            'trim',
            explode(';', (string) ($result['AdditionalErrorText'] ?? ''))
        )));

        return new self(
            vin: $vin,
            make: self::nullIfBlank($result['Make'] ?? null),
            model: self::nullIfBlank($result['Model'] ?? null),
            modelYear: self::nullIfBlank($result['ModelYear'] ?? null) !== null
                ? (int) $result['ModelYear']
                : null,
            engineCylinders: self::nullIfBlank($result['EngineCylinders'] ?? null),
            displacementL: self::nullIfBlank($result['DisplacementL'] ?? null),
            fuelTypePrimary: self::nullIfBlank($result['FuelTypePrimary'] ?? null),
            fuelTypeSecondary: self::nullIfBlank($result['FuelTypeSecondary'] ?? null),
            errorCode: $errorCode === '' ? 'unknown' : $errorCode,
            errorText: trim((string) ($result['ErrorText'] ?? '')),
            additionalErrorMessages: $additionalErrorMessages,
        );
    }

    /**
     * "0" means vPIC decoded the VIN cleanly. Any other code (including
     * multi-code strings like "1,6") means something was off — the VIN
     * checksum, an incomplete VIN, an unrecognized manufacturer, etc.
     */
    public function isFullyDecoded(): bool
    {
        return $this->errorCode === '0';
    }

    /**
     * True when vPIC gave us nothing usable at all (e.g. an unregistered
     * manufacturer). The lookup still "worked" — there's simply no vehicle
     * data to show.
     */
    public function hasNoData(): bool
    {
        return $this->make === null && $this->model === null && $this->modelYear === null;
    }

    /**
     * Labels for the core fields the VIN lookup form displays, with
     * "Unknown" standing in for anything vPIC did not decode.
     *
     * @return array<string, string>
     */
    public function displayFields(): array
    {
        return [
            'Make' => $this->make ?? 'Unknown',
            'Model' => $this->model ?? 'Unknown',
            'Model year' => $this->modelYear !== null ? (string) $this->modelYear : 'Unknown',
            'Engine cylinders' => $this->engineCylinders ?? 'Unknown',
            'Displacement (L)' => $this->displacementL ?? 'Unknown',
            'Fuel type' => $this->fuelTypePrimary ?? 'Unknown',
        ];
    }

    private static function nullIfBlank(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === null || $value === '' ? null : (string) $value;
    }
}
