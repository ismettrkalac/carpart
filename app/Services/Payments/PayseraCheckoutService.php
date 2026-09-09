<?php

namespace App\Services\Payments;

use App\Enums\OrderActorType;
use App\Enums\OrderStatusType;
use App\Enums\PaymentStatus;
use App\Mail\OrderPaymentConfirmedMail;
use App\Models\Order;
use App\Services\Inventory\StockDeductionService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Talks to Paysera's Checkout API (https://developers.paysera.com) over
 * plain HTTP via Laravel's HTTP client, rather than an SDK — this project
 * only needs a handful of operations, and doing that over an
 * Http::fake()-able client keeps payments testable the same way
 * VinDecoderService is, with no new Composer dependency.
 *
 * Checkout is hosted/redirect-based: the customer enters their card on
 * Paysera's own page (the returned payment_URL), which never touches
 * this app. That keeps this integration in PCI DSS SAQ A (the lightest
 * self-assessment tier) — embedding a card-entry widget on our own page
 * instead would push it into the much larger SAQ A-EP.
 *
 * FUTURE: this is the only place that talks to Paysera. Refunds or a
 * second provider would be added here rather than scattered through
 * controllers.
 */
class PayseraCheckoutService
{
    private const AUTH_URL = 'https://api.paysera.com/auth/realms/Paysera/protocol/openid-connect/token';

    private const API_BASE = 'https://api.paysera.com';

    private const TOKEN_CACHE_KEY = 'paysera:access_token';

    private readonly string $clientId;

    private readonly string $clientSecret;

    public function __construct(
        ?string $clientId = null,
        ?string $clientSecret = null,
        private readonly StockDeductionService $stockDeduction = new StockDeductionService,
    ) {
        $this->clientId = $clientId ?? (string) config('services.paysera.client_id');
        $this->clientSecret = $clientSecret ?? (string) config('services.paysera.client_secret');
    }

    /**
     * False when no client credentials are configured — callers should
     * fall back to the pre-payment "order received" flow rather than
     * error.
     */
    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    /**
     * Creates a Paysera order and a hosted payment link for it, and
     * returns the URL to redirect the customer to. Persists the Paysera
     * order id as the order's payment_reference, which is how the
     * webhook handler looks the order back up (via merchant_order_id,
     * which is this order's own uuid).
     *
     * @throws PayseraApiException
     */
    public function createSession(Order $order): string
    {
        $token = $this->accessToken();

        $orderResponse = $this->post($token, '/merchant-order/integration/v1/orders', [
            'redirect_urls' => [
                'success_url' => route('orders.show', $order),
                'failure_url' => route('orders.show', $order),
                'callback_url' => route('paysera.webhook'),
            ],
            'purchase' => [
                'reference' => $order->uuid,
                'amount' => $order->total_cents,
                'currency' => $order->currency,
            ],
        ], 'create the order');

        $payseraOrderId = (string) $orderResponse->json('order_id');

        $linkResponse = $this->post($token, '/checkout-payment-link/integration/v1/payment-links', [
            'order_id' => $payseraOrderId,
            'name' => 'Order '.$order->orderNumber(),
            'purchase' => ['amount' => $order->total_cents],
        ], 'create the payment link');

        $order->forceFill([
            'payment_provider' => 'paysera',
            'payment_reference' => $payseraOrderId,
        ])->save();

        return (string) $linkResponse->json('payment_URL');
    }

    /**
     * Applies an already signature-verified Paysera "order" event to the
     * order it references. Any other event type is a silent no-op.
     *
     * Paysera's published API schema doesn't define a formal `status`
     * enum for orders/payments, so "paid" is decided from the numeric
     * amount/amount_paid fields rather than a status string — confirm
     * this against a real sandbox payload if Paysera's payload shape
     * changes.
     *
     * Idempotent and concurrency-safe: the order row is locked before its
     * payment_status is checked, so two overlapping deliveries of the
     * same event (Paysera, like most providers, only guarantees
     * at-least-once delivery) can't both pass the PendingPayment guard —
     * only the first transitions the order, records history, and
     * deducts stock; the second is a no-op.
     *
     * @param  array<string, mixed>  $event
     */
    public function handleWebhookEvent(array $event): void
    {
        if (($event['event']['type'] ?? null) !== 'order') {
            return;
        }

        $orderData = $event['order'] ?? null;
        if (! is_array($orderData)) {
            return;
        }

        $merchantOrderId = $orderData['merchant_order_id'] ?? null;
        $amount = $orderData['amount'] ?? null;
        $amountPaid = $orderData['amount_paid'] ?? null;

        if (! is_string($merchantOrderId) || ! is_int($amount) || ! is_int($amountPaid)) {
            Log::warning('Paysera webhook order payload missing expected fields', ['order' => $orderData]);

            return;
        }

        if ($amountPaid < $amount) {
            return;
        }

        $order = DB::transaction(function () use ($merchantOrderId, $orderData): ?Order {
            $order = Order::where('uuid', $merchantOrderId)->lockForUpdate()->first();

            if ($order === null) {
                Log::warning('Paysera webhook referenced an unknown order', ['merchant_order_id' => $merchantOrderId]);

                return null;
            }

            if ($order->payment_status !== PaymentStatus::PendingPayment) {
                return null;
            }

            $order->forceFill(['payment_status' => PaymentStatus::Paid])->save();

            $order->statusHistories()->create([
                'status_type' => OrderStatusType::Payment,
                'from_status' => PaymentStatus::PendingPayment->value,
                'to_status' => PaymentStatus::Paid->value,
                'actor_type' => OrderActorType::System,
                'actor_id' => null,
                'note' => 'Paysera order '.($orderData['paysera_order_id'] ?? $merchantOrderId).'.',
            ]);

            $this->stockDeduction->deductForOrder($order);

            return $order;
        });

        // Null means the webhook was a no-op (unknown order, or a duplicate
        // delivery of an already-paid order) — nothing changed, so no email.
        if ($order !== null) {
            Mail::to($order->email)->queue(new OrderPaymentConfirmedMail($order));
        }
    }

    /**
     * OAuth2 client_credentials access token, cached just under its
     * 3600-second lifetime (no refresh token is issued — a new one is
     * requested once the cached value expires).
     *
     * @throws PayseraApiException
     */
    private function accessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, 3300, function (): string {
            try {
                $response = Http::asForm()->post(self::AUTH_URL, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);
            } catch (ConnectionException $exception) {
                throw new PayseraApiException('Could not reach Paysera to authenticate.', previous: $exception);
            }

            if ($response->failed()) {
                throw new PayseraApiException('Paysera rejected the authentication request.');
            }

            return (string) $response->json('access_token');
        });
    }

    /**
     * @param  array<string, mixed>  $body
     *
     * @throws PayseraApiException
     */
    private function post(string $token, string $path, array $body, string $action): Response
    {
        try {
            $response = Http::withToken($token)->post(self::API_BASE.$path, $body);
        } catch (ConnectionException $exception) {
            throw new PayseraApiException("Could not reach Paysera to {$action}.", previous: $exception);
        }

        if ($response->failed()) {
            Log::error("Paysera request failed: {$action}", [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new PayseraApiException("Paysera rejected the request to {$action}.");
        }

        return $response;
    }
}
