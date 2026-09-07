<?php

namespace App\Http\Controllers;

use App\Http\Requests\VinLookupRequest;
use App\Services\Vpic\VinDecoderService;
use App\Services\Vpic\VpicLookupException;
use Illuminate\Contracts\View\View;

class VinLookupController extends Controller
{
    /**
     * Show the VIN lookup form, decoding the VIN when one was submitted.
     */
    public function __invoke(VinLookupRequest $request, VinDecoderService $decoder): View
    {
        $vin = $request->validated('vin');

        if ($vin === null) {
            return view('vin-lookup.show');
        }

        try {
            $vehicle = $decoder->decodeVin($vin, $request->validated('modelyear'));
        } catch (VpicLookupException $exception) {
            return view('vin-lookup.show', [
                'vin' => $vin,
                'lookupError' => $exception->getMessage(),
            ]);
        }

        return view('vin-lookup.show', [
            'vin' => $vin,
            'vehicle' => $vehicle,
        ]);
    }
}
