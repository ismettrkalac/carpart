<?php

namespace Tests\Feature;

use App\Enums\OrderStatusType;
use App\Enums\PaymentStatus;
use App\Mail\OrderPaymentConfirmedMail;
use App\Models\Order;
use App\Models\Part;
use App\Models\StockReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PayseraWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_SECRET = 'client_secret_test';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paysera.client_secret' => self::CLIENT_SECRET]);
    }

    public function test_a_fully_paid_order_event_marks_the_order_paid(): void
    {
        Mail::fake();
        $order = Order::factory()->create();
        $order->forceFill(['payment_provider' => 'paysera', 'payment_reference' => 'paysera-order-1'])->save();

        $response = $this->postSignedWebhook($this->orderEvent($order->uuid, 'paysera-order-1', $order->total_cents, $order->total_cents));

        $response->assertOk();
        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);

        $entry = $order->statusHistories()->first();
        $this->assertSame(OrderStatusType::Payment, $entry->status_type);
        $this->assertSame('pending_payment', $entry->from_status);
        $this->assertSame('paid', $entry->to_status);
    }

    public function test_a_partially_paid_order_event_does_not_change_the_order(): void
    {
        $order = Order::factory()->create();
        $order->forceFill(['payment_provider' => 'paysera', 'payment_reference' => 'paysera-order-2'])->save();

        $response = $this->postSignedWebhook($this->orderEvent($order->uuid, 'paysera-order-2', $order->total_cents, (int) ($order->total_cents / 2)));

        $response->assertOk();
        $this->assertSame(PaymentStatus::PendingPayment, $order->fresh()->payment_status);
    }

    public function test_a_duplicate_delivery_of_the_same_event_is_a_safe_no_op(): void
    {
        Mail::fake();
        $order = Order::factory()->create();
        $order->forceFill(['payment_provider' => 'paysera', 'payment_reference' => 'paysera-order-3'])->save();
        $event = $this->orderEvent($order->uuid, 'paysera-order-3', $order->total_cents, $order->total_cents);

        $this->postSignedWebhook($event)->assertOk();
        $this->postSignedWebhook($event)->assertOk();

        $this->assertSame(1, $order->statusHistories()->count());
    }

    public function test_a_fully_paid_order_event_queues_a_payment_confirmation_email(): void
    {
        Mail::fake();
        $order = Order::factory()->create();
        $order->forceFill(['payment_provider' => 'paysera', 'payment_reference' => 'paysera-order-mail-1'])->save();

        $this->postSignedWebhook($this->orderEvent($order->uuid, 'paysera-order-mail-1', $order->total_cents, $order->total_cents))
            ->assertOk();

        Mail::assertQueued(
            OrderPaymentConfirmedMail::class,
            fn (OrderPaymentConfirmedMail $mail): bool => $mail->hasTo($order->email) && $mail->order->is($order),
        );
    }

    public function test_a_duplicate_delivery_of_the_same_event_does_not_queue_a_second_email(): void
    {
        Mail::fake();
        $order = Order::factory()->create();
        $order->forceFill(['payment_provider' => 'paysera', 'payment_reference' => 'paysera-order-mail-2'])->save();
        $event = $this->orderEvent($order->uuid, 'paysera-order-mail-2', $order->total_cents, $order->total_cents);

        $this->postSignedWebhook($event)->assertOk();
        $this->postSignedWebhook($event)->assertOk();

        Mail::assertQueuedCount(1);
    }

    public function test_a_partially_paid_order_event_does_not_queue_a_payment_confirmation_email(): void
    {
        Mail::fake();
        $order = Order::factory()->create();
        $order->forceFill(['payment_provider' => 'paysera', 'payment_reference' => 'paysera-order-mail-3'])->save();

        $this->postSignedWebhook($this->orderEvent($order->uuid, 'paysera-order-mail-3', $order->total_cents, (int) ($order->total_cents / 2)))
            ->assertOk();

        Mail::assertNothingQueued();
    }

    public function test_a_paid_order_event_decrements_stock_for_its_items(): void
    {
        Mail::fake();
        $part = Part::factory()->create(['stock_quantity' => 10]);
        $order = Order::factory()->create();
        $order->forceFill(['payment_provider' => 'paysera', 'payment_reference' => 'paysera-order-4'])->save();
        $order->items()->create([
            'part_id' => $part->id,
            'sku' => $part->sku,
            'name' => $part->name,
            'unit_price_cents' => 1000,
            'quantity' => 3,
            'line_total_cents' => 3000,
        ]);

        $this->postSignedWebhook($this->orderEvent($order->uuid, 'paysera-order-4', $order->total_cents, $order->total_cents))
            ->assertOk();

        $this->assertSame(7, $part->fresh()->stock_quantity);
    }

    public function test_a_paid_order_event_releases_the_orders_stock_reservation(): void
    {
        Mail::fake();
        $part = Part::factory()->create(['stock_quantity' => 10]);
        $order = Order::factory()->create();
        $order->forceFill(['payment_provider' => 'paysera', 'payment_reference' => 'paysera-order-reservation'])->save();
        $order->items()->create([
            'part_id' => $part->id,
            'sku' => $part->sku,
            'name' => $part->name,
            'unit_price_cents' => 1000,
            'quantity' => 3,
            'line_total_cents' => 3000,
        ]);
        StockReservation::factory()->for($part)->for($order)->create(['quantity' => 3]);

        $this->postSignedWebhook($this->orderEvent($order->uuid, 'paysera-order-reservation', $order->total_cents, $order->total_cents))
            ->assertOk();

        $this->assertSame(0, StockReservation::where('order_id', $order->id)->count());
    }

    public function test_a_duplicate_delivery_does_not_double_decrement_stock(): void
    {
        Mail::fake();
        $part = Part::factory()->create(['stock_quantity' => 10]);
        $order = Order::factory()->create();
        $order->forceFill(['payment_provider' => 'paysera', 'payment_reference' => 'paysera-order-5'])->save();
        $order->items()->create([
            'part_id' => $part->id,
            'sku' => $part->sku,
            'name' => $part->name,
            'unit_price_cents' => 1000,
            'quantity' => 3,
            'line_total_cents' => 3000,
        ]);
        $event = $this->orderEvent($order->uuid, 'paysera-order-5', $order->total_cents, $order->total_cents);

        $this->postSignedWebhook($event)->assertOk();
        $this->postSignedWebhook($event)->assertOk();

        $this->assertSame(7, $part->fresh()->stock_quantity);
    }

    public function test_an_unpaid_order_never_touches_stock(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10]);
        $order = Order::factory()->create();
        $order->forceFill(['payment_provider' => 'paysera', 'payment_reference' => 'paysera-order-6'])->save();
        $order->items()->create([
            'part_id' => $part->id,
            'sku' => $part->sku,
            'name' => $part->name,
            'unit_price_cents' => 1000,
            'quantity' => 3,
            'line_total_cents' => 3000,
        ]);

        // A partial-payment event never marks the order paid, so it must
        // never decrement stock either.
        $this->postSignedWebhook($this->orderEvent($order->uuid, 'paysera-order-6', $order->total_cents, (int) ($order->total_cents / 2)))
            ->assertOk();

        $this->assertSame(10, $part->fresh()->stock_quantity);
    }

    public function test_a_non_order_event_is_a_safe_no_op(): void
    {
        $response = $this->postSignedWebhook([
            'event' => ['type' => 'payment', 'name' => 'something'],
            'payment' => ['id' => 'whatever'],
        ]);

        $response->assertOk();
    }

    public function test_an_event_for_an_unknown_order_is_a_safe_no_op(): void
    {
        $response = $this->postSignedWebhook($this->orderEvent('00000000-0000-0000-0000-000000000000', 'unknown-ref', 1000, 1000));

        $response->assertOk();
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        $payload = json_encode($this->orderEvent('some-uuid', 'ref', 1000, 1000));

        $response = $this->call('POST', route('paysera.webhook'), server: [
            'HTTP_X-Paysera-Signature' => 'not-a-real-signature',
        ], content: $payload);

        $response->assertStatus(400);
    }

    public function test_a_missing_signature_header_is_rejected(): void
    {
        $response = $this->postJson(route('paysera.webhook'), $this->orderEvent('some-uuid', 'ref', 1000, 1000));

        $response->assertStatus(400);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderEvent(string $merchantOrderId, string $payseraOrderId, int $amount, int $amountPaid): array
    {
        return [
            'event' => ['name' => 'amount_paid_updated', 'type' => 'order'],
            'order' => [
                'paysera_order_id' => $payseraOrderId,
                'merchant_order_id' => $merchantOrderId,
                'amount' => $amount,
                'amount_paid' => $amountPaid,
                'currency' => 'USD',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function postSignedWebhook(array $event): TestResponse
    {
        $payload = json_encode($event);
        $signature = hash_hmac('sha256', $payload, self::CLIENT_SECRET);

        return $this->call('POST', route('paysera.webhook'), server: [
            'HTTP_X-Paysera-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], content: $payload);
    }
}
