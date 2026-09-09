<?php

namespace Tests\Feature;

use App\Enums\OrderActorType;
use App\Enums\OrderStatusType;
use App\Enums\PaymentStatus;
use App\Mail\OrderRefundedMail;
use App\Models\Order;
use App\Services\Payments\PayseraApiException;
use App\Services\Payments\PayseraCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PayseraRefundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paysera.client_id' => 'test_client_id',
            'services.paysera.client_secret' => 'test_client_secret',
        ]);
    }

    public function test_refunding_a_paid_order_marks_it_refunded(): void
    {
        Mail::fake();
        $this->fakePayseraApi();
        $order = $this->paidOrder();

        app(PayseraCheckoutService::class)->refund($order, OrderActorType::Staff, 42);

        $this->assertSame(PaymentStatus::Refunded, $order->fresh()->payment_status);
    }

    public function test_refunding_records_status_history_with_the_staff_actor_and_reason(): void
    {
        Mail::fake();
        $this->fakePayseraApi();
        $order = $this->paidOrder();

        app(PayseraCheckoutService::class)->refund($order, OrderActorType::Staff, 42, 'Customer changed their mind.');

        $entry = $order->statusHistories()->first();
        $this->assertSame(OrderStatusType::Payment, $entry->status_type);
        $this->assertSame('paid', $entry->from_status);
        $this->assertSame('refunded', $entry->to_status);
        $this->assertSame(OrderActorType::Staff, $entry->actor_type);
        $this->assertSame(42, $entry->actor_id);
        $this->assertSame('Customer changed their mind.', $entry->note);
    }

    public function test_refunding_calls_paysera_with_the_payment_id_amount_currency_and_an_idempotency_key(): void
    {
        Mail::fake();
        $this->fakePayseraApi();
        $order = $this->paidOrder();

        app(PayseraCheckoutService::class)->refund($order, OrderActorType::Staff, null);

        Http::assertSent(function ($request) use ($order) {
            return $request->url() === 'https://api.paysera.com/payment-executor/integration/v1/payments/settled-payment-1/refunds'
                && $request['amount'] === $order->total_cents
                && $request['currency'] === $order->currency
                && $request['reference'] === $order->uuid
                && $request->hasHeader('Idempotency-Key', "{$order->uuid}:refund");
        });
    }

    public function test_refunding_queues_a_refund_confirmation_email(): void
    {
        Mail::fake();
        $this->fakePayseraApi();
        $order = $this->paidOrder();

        app(PayseraCheckoutService::class)->refund($order, OrderActorType::Staff, null);

        Mail::assertQueued(
            OrderRefundedMail::class,
            fn (OrderRefundedMail $mail): bool => $mail->hasTo($order->email) && $mail->order->is($order),
        );
    }

    public function test_refunding_a_pending_payment_order_is_rejected(): void
    {
        Http::fake();
        $order = Order::factory()->create();

        $this->expectException(PayseraApiException::class);

        app(PayseraCheckoutService::class)->refund($order, OrderActorType::Staff, null);
    }

    public function test_refunding_an_order_with_no_recorded_payment_id_is_rejected(): void
    {
        Http::fake();
        $order = Order::factory()->create();
        $order->forceFill(['payment_status' => PaymentStatus::Paid, 'payment_id' => null])->save();

        $this->expectException(PayseraApiException::class);

        app(PayseraCheckoutService::class)->refund($order, OrderActorType::Staff, null);
    }

    public function test_a_failed_paysera_refund_request_does_not_change_local_state_or_send_an_email(): void
    {
        Mail::fake();
        Http::fake([
            '*openid-connect/token' => Http::response(['access_token' => 'fake_access_token', 'expires_in' => 3600]),
            'api.paysera.com/payment-executor/*' => Http::response(['error' => 'rejected'], 422),
        ]);
        $order = $this->paidOrder();

        try {
            app(PayseraCheckoutService::class)->refund($order, OrderActorType::Staff, null);
        } catch (PayseraApiException) {
            // expected
        }

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        Mail::assertNothingQueued();
    }

    public function test_the_refunded_banner_shows_on_the_public_receipt_page(): void
    {
        $order = Order::factory()->create(['user_id' => null]);
        $order->forceFill(['payment_status' => PaymentStatus::Refunded])->save();

        $response = $this->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('This order was refunded.');
    }

    private function paidOrder(): Order
    {
        $order = Order::factory()->create();
        $order->forceFill([
            'payment_provider' => 'paysera',
            'payment_status' => PaymentStatus::Paid,
            'payment_id' => 'settled-payment-1',
        ])->save();

        return $order;
    }

    private function fakePayseraApi(): void
    {
        Http::fake([
            '*openid-connect/token' => Http::response(['access_token' => 'fake_access_token', 'expires_in' => 3600]),
            'api.paysera.com/payment-executor/*' => Http::response([
                'refund_id' => 'refund-uuid-123',
                'status' => 'initiated',
            ]),
        ]);
    }
}
