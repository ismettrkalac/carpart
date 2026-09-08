<?php

namespace App\Http\Controllers;

use App\Services\Payments\PayseraCheckoutService;
use App\Services\Payments\PayseraSignature;
use App\Services\Payments\PayseraWebhookException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PayseraWebhookController extends Controller
{
    public function __construct(private readonly PayseraCheckoutService $paysera) {}

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();

        try {
            PayseraSignature::verify(
                $payload,
                $request->header('X-Paysera-Signature'),
                $request->header('X-Paysera-Created-At'),
                (string) config('services.paysera.client_secret'),
            );
        } catch (PayseraWebhookException $exception) {
            Log::warning('Rejected Paysera webhook', ['message' => $exception->getMessage()]);

            return response('Invalid signature.', 400);
        }

        $event = json_decode($payload, associative: true);

        if (! is_array($event)) {
            return response('Invalid payload.', 400);
        }

        $this->paysera->handleWebhookEvent($event);

        return response('OK', 200);
    }
}
