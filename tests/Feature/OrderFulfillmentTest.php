<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderActorType;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderNote;
use App\Models\Part;
use App\Models\User;
use App\Services\Orders\OrderFulfillmentService;
use App\Services\Orders\OrderNoteService;
use App\Services\Orders\OrderShipmentException;
use App\Services\Orders\OrderShipmentService;
use App\Services\Orders\OrderTransitionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * payment_status/fulfillment_status are deliberately excluded from
     * Order::$fillable (see Order.php) so they can never change via mass
     * assignment — only through OrderFulfillmentService. Tests must use
     * forceFill() to set up a starting state, the same escape hatch a
     * migration/seeder would use.
     */
    private function makeOrder(PaymentStatus $payment = PaymentStatus::PendingPayment, FulfillmentStatus $fulfillment = FulfillmentStatus::Unfulfilled): Order
    {
        $order = Order::factory()->create();
        $order->forceFill([
            'payment_status' => $payment,
            'fulfillment_status' => $fulfillment,
        ])->save();

        return $order->fresh();
    }

    public function test_processing_is_blocked_while_payment_is_pending(): void
    {
        $order = $this->makeOrder(payment: PaymentStatus::PendingPayment);

        $this->expectException(OrderTransitionException::class);

        app(OrderFulfillmentService::class)->transitionTo(
            $order,
            FulfillmentStatus::Processing,
            OrderActorType::Staff,
            null,
        );
    }

    public function test_processing_succeeds_once_payment_is_marked_paid(): void
    {
        $order = $this->makeOrder(payment: PaymentStatus::Paid);

        $updated = app(OrderFulfillmentService::class)->transitionTo(
            $order,
            FulfillmentStatus::Processing,
            OrderActorType::Staff,
            null,
        );

        $this->assertSame(FulfillmentStatus::Processing, $updated->fulfillment_status);
    }

    public function test_skipping_a_stage_is_rejected(): void
    {
        $order = $this->makeOrder(payment: PaymentStatus::Paid, fulfillment: FulfillmentStatus::Unfulfilled);

        $this->expectException(OrderTransitionException::class);

        // Unfulfilled -> Shipped is not a valid direct transition.
        app(OrderFulfillmentService::class)->transitionTo(
            $order,
            FulfillmentStatus::Shipped,
            OrderActorType::Staff,
            null,
        );
    }

    public function test_a_delivered_order_cannot_transition_further(): void
    {
        $order = $this->makeOrder(payment: PaymentStatus::Paid, fulfillment: FulfillmentStatus::Delivered);

        $this->expectException(OrderTransitionException::class);

        app(OrderFulfillmentService::class)->transitionTo(
            $order,
            FulfillmentStatus::Cancelled,
            OrderActorType::Staff,
            null,
        );
    }

    public function test_cancellation_is_allowed_while_unfulfilled_even_if_unpaid(): void
    {
        $order = $this->makeOrder(payment: PaymentStatus::PendingPayment, fulfillment: FulfillmentStatus::Unfulfilled);

        $updated = app(OrderFulfillmentService::class)->cancel($order, OrderActorType::Staff, null);

        $this->assertSame(FulfillmentStatus::Cancelled, $updated->fulfillment_status);
    }

    public function test_a_shipped_order_cannot_be_cancelled(): void
    {
        $order = $this->makeOrder(payment: PaymentStatus::Paid, fulfillment: FulfillmentStatus::Shipped);

        $this->expectException(OrderTransitionException::class);

        app(OrderFulfillmentService::class)->cancel($order, OrderActorType::Staff, null);
    }

    public function test_a_valid_transition_records_status_history_with_actor_and_timestamp(): void
    {
        $order = $this->makeOrder(payment: PaymentStatus::Paid, fulfillment: FulfillmentStatus::Unfulfilled);

        app(OrderFulfillmentService::class)->transitionTo(
            $order,
            FulfillmentStatus::Processing,
            OrderActorType::Staff,
            42,
            'Prepared for shipment.',
        );

        $entry = $order->statusHistories()->first();
        $this->assertNotNull($entry);
        $this->assertSame('unfulfilled', $entry->from_status);
        $this->assertSame('processing', $entry->to_status);
        $this->assertSame(OrderActorType::Staff, $entry->actor_type);
        $this->assertSame(42, $entry->actor_id);
        $this->assertSame('Prepared for shipment.', $entry->note);
        $this->assertNotNull($entry->created_at);
    }

    public function test_repeating_the_same_transition_is_a_safe_no_op(): void
    {
        $order = $this->makeOrder(payment: PaymentStatus::Paid, fulfillment: FulfillmentStatus::Unfulfilled);
        $service = app(OrderFulfillmentService::class);

        $service->transitionTo($order, FulfillmentStatus::Processing, OrderActorType::Staff, null);
        // Simulate a double-click / retried request with the already-updated order.
        $service->transitionTo($order->fresh(), FulfillmentStatus::Processing, OrderActorType::Staff, null);

        $this->assertSame(FulfillmentStatus::Processing, $order->fresh()->fulfillment_status);
        $this->assertSame(1, $order->statusHistories()->count());
    }

    public function test_fulfillment_actions_never_change_stock(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 25]);
        $order = $this->makeOrder(payment: PaymentStatus::Paid, fulfillment: FulfillmentStatus::Unfulfilled);
        $order->items()->create([
            'part_id' => $part->id,
            'sku' => $part->sku,
            'name' => $part->name,
            'unit_price_cents' => 1000,
            'quantity' => 3,
            'line_total_cents' => 3000,
        ]);

        $service = app(OrderFulfillmentService::class);
        $service->transitionTo($order, FulfillmentStatus::Processing, OrderActorType::Staff, null);
        $service->transitionTo($order->fresh(), FulfillmentStatus::Shipped, OrderActorType::Staff, null);
        $service->transitionTo($order->fresh(), FulfillmentStatus::Delivered, OrderActorType::Staff, null);

        $this->assertSame(25, $part->fresh()->stock_quantity);
    }

    public function test_internal_notes_are_never_shown_on_the_customer_account_page(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        app(OrderNoteService::class)->add($order, 'Called the customer about a delayed part.', null);

        $response = $this->actingAs($user)->get(route('account.orders.show', $order));

        $response->assertOk();
        $response->assertDontSee('Called the customer about a delayed part.');
    }

    public function test_internal_notes_are_never_shown_on_the_public_order_receipt(): void
    {
        $order = Order::factory()->create(['user_id' => null]);
        app(OrderNoteService::class)->add($order, 'Flagged for fraud review.', null);

        $response = $this->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertDontSee('Flagged for fraud review.');
    }

    public function test_notes_are_stored_separately_from_status_history(): void
    {
        $order = Order::factory()->create();
        app(OrderNoteService::class)->add($order, 'Internal note.', null);

        $this->assertSame(1, OrderNote::count());
        $this->assertSame(0, $order->statusHistories()->count());
    }

    public function test_shipment_update_rejects_a_non_https_tracking_url(): void
    {
        $order = Order::factory()->create();

        $this->expectException(OrderShipmentException::class);

        app(OrderShipmentService::class)->update($order, 'UPS', '12345', 'http://insecure.example.com');
    }

    public function test_shipment_update_accepts_a_valid_https_tracking_url(): void
    {
        $order = Order::factory()->create();

        $updated = app(OrderShipmentService::class)->update($order, 'UPS', '12345', 'https://ups.com/track/12345');

        $this->assertSame('UPS', $updated->carrier);
        $this->assertSame('https://ups.com/track/12345', $updated->tracking_url);
    }
}
