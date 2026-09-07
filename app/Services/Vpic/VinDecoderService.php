<?php

namespace App\Services\Vpic;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Decodes VINs via the free NHTSA vPIC API (vehicle identification only —
 * no parts, prices, stock, or compatibility data). See:
 * https://vpic.nhtsa.dot.gov/api/
 */
final class VinDecoderService
{
    private readonly string $baseUrl;

    private readonly int $timeoutSeconds;

    private readonly int $cacheTtlSeconds;

    public function __construct(?string $baseUrl = null, ?int $timeoutSeconds = null, ?int $cacheTtlSeconds = null)
    {
        $this->baseUrl = $baseUrl ?? (string) config('services.vpic.base_url');
        $this->timeoutSeconds = $timeoutSeconds ?? (int) config('services.vpic.timeout', 5);
        $this->cacheTtlSeconds = $cacheTtlSeconds ?? (int) config('services.vpic.cache_ttl', 86400);
    }

    /**
     * @throws VpicLookupException when the API can't be reached or returns
     *                             a response we can't parse at all. A VIN
     *                             that decodes incompletely is NOT an
     *                             error — it's a normal, partial result.
     */
    public function decodeVin(string $vin, ?int $modelYear = null): DecodedVehicle
    {
        $vin = strtoupper(trim($vin));
        $cacheKey = $this->cacheKey($vin, $modelYear);

        $cached = Cache::get($cacheKey);
        if ($cached instanceof DecodedVehicle) {
            return $cached;
        }

        $result = $this->fetch($vin, $modelYear);
        $decoded = DecodedVehicle::fromApiResult($vin, $result);

        // The request itself succeeded — cache it even if the VIN only
        // decoded partially, so we don't hammer the free public API with
        // repeat lookups for the same (possibly bad) VIN.
        Cache::put($cacheKey, $decoded, $this->cacheTtlSeconds);

        return $decoded;
    }

    /**
     * @return array<string, mixed> the first (only) entry of "Results"
     *
     * @throws VpicLookupException
     */
    private function fetch(string $vin, ?int $modelYear): array
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeoutSeconds)
                ->get("/vehicles/DecodeVinValues/{$vin}", array_filter([
                    'format' => 'json',
                    'modelyear' => $modelYear,
                ]));
        } catch (ConnectionException $exception) {
            Log::warning('vPIC VIN lookup connection failure', ['vin' => $vin, 'message' => $exception->getMessage()]);

            throw new VpicLookupException('Could not reach the vehicle lookup service. Please try again shortly.', previous: $exception);
        }

        if ($response->failed()) {
            Log::warning('vPIC VIN lookup HTTP failure', ['vin' => $vin, 'status' => $response->status()]);

            throw new VpicLookupException('The vehicle lookup service returned an error. Please try again shortly.');
        }

        $result = $response->json('Results.0');

        if (! is_array($result)) {
            Log::warning('vPIC VIN lookup returned an unexpected payload', ['vin' => $vin]);

            throw new VpicLookupException('The vehicle lookup service returned an unexpected response.');
        }

        return $result;
    }

    private function cacheKey(string $vin, ?int $modelYear): string
    {
        return sprintf('vpic:decode-vin:%s:%s', $vin, $modelYear ?? 'any');
    }
}
