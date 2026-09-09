<?php

namespace Tests\Feature;

use App\Enums\FulfillmentStatus;
use App\Enums\PartStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_redirects_to_cart_when_empty(): void
    {
        $response = $this->get(route('checkout.create'));

        $response->assertRedirect(route('cart.index'));
    }

    public function test_checkout_redirects_to_cart_when_an_item_needs_attention(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 5, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 5]);
        $part->update(['stock_quantity' => 0]);

        $response = $this->get(route('checkout.create'));

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHasErrors('cart');
    }

    public function test_checkout_page_shows_totals_matching_the_cart(): void
    {
        $part = Part::factory()->create(['base_price_cents' => 10000, 'stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 2]);

        $response = $this->get(route('checkout.create'));

        $response->assertOk();
        // subtotal 20000 cents, under the 10000-cent free-shipping threshold
        // configured for tests below, so shipping/tax apply.
        $response->assertSee('$200.00'); // subtotal
    }

    public function test_submitting_checkout_creates_a_pending_payment_order_without_changing_stock(): void
    {
        $part = Part::factory()->create(['base_price_cents' => 5000, 'stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part, 2);

        $response = $this->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $this->assertSame(1, Order::count());
        $order = Order::first();
        $response->assertRedirect(route('orders.show', $order));

        $this->assertSame(PaymentStatus::PendingPayment, $order->payment_status);
        $this->assertSame(FulfillmentStatus::Unfulfilled, $order->fulfillment_status);
        $this->assertSame(10, $part->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'part_id' => $part->id, 'quantity' => 2]);

        $this->assertSame(1, $order->items->count());
        $item = $order->items->first();
        $this->assertSame($part->sku, $item->sku);
        $this->assertSame($part->name, $item->name);
        $this->assertSame(5000, $item->unit_price_cents);
        $this->assertSame(2, $item->quantity);
        $this->assertSame(10000, $item->line_total_cents);
    }

    public function test_submitting_checkout_queues_an_order_confirmation_email(): void
    {
        Mail::fake();
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part);

        $this->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $order = Order::first();
        Mail::assertQueued(
            OrderConfirmationMail::class,
            fn (OrderConfirmationMail $mail): bool => $mail->hasTo($order->email) && $mail->order->is($order),
        );
    }

    public function test_duplicate_submission_of_the_same_checkout_only_queues_one_confirmation_email(): void
    {
        Mail::fake();
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part);
        $payload = $this->validCheckoutPayload($token);

        $this->post(route('checkout.store'), $payload);
        $this->post(route('checkout.store'), $payload);

        Mail::assertQueuedCount(1);
    }

    public function test_submitting_checkout_clears_the_cart(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part, 1);

        $this->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $this->assertEmpty(session('cart', []));
    }

    public function test_guest_checkout_leaves_the_order_unowned(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part);

        $this->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $this->assertNull(Order::first()->user_id);
    }

    public function test_authenticated_checkout_associates_the_user(): void
    {
        $user = User::factory()->create();
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);

        $this->actingAs($user);
        $token = $this->addToCartAndStartCheckout($part);
        $this->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $this->assertSame($user->id, Order::first()->user_id);
    }

    public function test_a_guest_sees_an_empty_checkout_form(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 1]);

        $response = $this->get(route('checkout.create'));

        $this->assertSame([], $response->viewData('prefill'));
    }

    public function test_a_first_time_logged_in_customer_gets_only_their_email_prefilled(): void
    {
        $user = User::factory()->create(['email' => 'returning@example.com']);
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 1]);

        $response = $this->actingAs($user)->get(route('checkout.create'));

        $this->assertSame(['email' => 'returning@example.com'], $response->viewData('prefill'));
    }

    public function test_a_returning_customer_gets_their_last_orders_address_prefilled(): void
    {
        $user = User::factory()->create(['email' => 'returning@example.com']);
        Order::factory()->create([
            'user_id' => $user->id,
            'shipping_name' => 'Jane Mechanic',
            'shipping_line1' => '123 Garage Row',
            'shipping_line2' => null,
            'shipping_city' => 'Springfield',
            'shipping_state' => 'IL',
            'shipping_postal_code' => '62704',
            'shipping_country' => 'US',
            'billing_name' => 'Jane Mechanic',
            'billing_line1' => '123 Garage Row',
            'billing_line2' => null,
            'billing_city' => 'Springfield',
            'billing_state' => 'IL',
            'billing_postal_code' => '62704',
            'billing_country' => 'US',
        ]);
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 1]);

        $response = $this->actingAs($user)->get(route('checkout.create'));

        $prefill = $response->viewData('prefill');
        $this->assertSame('returning@example.com', $prefill['email']);
        $this->assertSame('Jane Mechanic', $prefill['shipping_name']);
        $this->assertSame('123 Garage Row', $prefill['shipping_line1']);
        $this->assertSame('Springfield', $prefill['shipping_city']);
        $this->assertFalse($prefill['billing_different']);
        $this->assertArrayNotHasKey('billing_name', $prefill);
    }

    public function test_a_returning_customer_with_a_different_billing_address_gets_it_prefilled_too(): void
    {
        $user = User::factory()->create();
        Order::factory()->create([
            'user_id' => $user->id,
            'billing_name' => 'Jane Mechanic LLC',
        ]);
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 1]);

        $response = $this->actingAs($user)->get(route('checkout.create'));

        $prefill = $response->viewData('prefill');
        $this->assertTrue($prefill['billing_different']);
        $this->assertSame('Jane Mechanic LLC', $prefill['billing_name']);
    }

    public function test_prefilled_fields_appear_in_the_rendered_form(): void
    {
        $user = User::factory()->create(['email' => 'returning@example.com']);
        Order::factory()->create(['user_id' => $user->id, 'shipping_name' => 'Jane Mechanic']);
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 1]);

        $response = $this->actingAs($user)->get(route('checkout.create'));

        $response->assertSee('returning@example.com', false);
        $response->assertSee('Jane Mechanic', false);
    }

    public function test_billing_address_defaults_to_shipping_when_not_marked_different(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part);

        $this->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $order = Order::first();
        $this->assertSame($order->shipping_name, $order->billing_name);
        $this->assertSame($order->shipping_line1, $order->billing_line1);
        $this->assertSame($order->shipping_city, $order->billing_city);
    }

    public function test_a_separate_billing_address_is_stored_when_marked_different(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part);

        $this->post(route('checkout.store'), $this->validCheckoutPayload($token, [
            'billing_different' => '1',
            'billing_name' => 'Billing Co',
            'billing_line1' => '99 Invoice Ave',
            'billing_city' => 'Metropolis',
            'billing_state' => 'NY',
            'billing_postal_code' => '10001',
            'billing_country' => 'US',
        ]));

        $order = Order::first();
        $this->assertSame('Billing Co', $order->billing_name);
        $this->assertSame('99 Invoice Ave', $order->billing_line1);
        $this->assertNotSame($order->shipping_name, $order->billing_name);
    }

    public function test_billing_fields_are_required_when_marked_different(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part);

        $response = $this->post(route('checkout.store'), $this->validCheckoutPayload($token, [
            'billing_different' => '1',
        ]));

        $response->assertSessionHasErrors(['billing_name', 'billing_line1', 'billing_city', 'billing_state', 'billing_postal_code', 'billing_country']);
        $this->assertSame(0, Order::count());
    }

    public function test_shipping_fields_are_required(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part);

        $response = $this->post(route('checkout.store'), ['checkout_token' => $token]);

        $response->assertSessionHasErrors(['email', 'shipping_name', 'shipping_line1', 'shipping_city', 'shipping_state', 'shipping_postal_code', 'shipping_country']);
        $this->assertSame(0, Order::count());
    }

    public function test_a_price_change_after_the_checkout_page_loaded_blocks_order_creation_and_explains_why(): void
    {
        $part = Part::factory()->create(['name' => 'Brake Rotor', 'base_price_cents' => 5000, 'stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part);

        $part->update(['base_price_cents' => 7500]);

        $response = $this->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $response->assertOk(); // re-rendered the checkout page directly, not redirected
        $response->assertSee('price changed');
        $response->assertSee('Brake Rotor');
        $this->assertSame(0, Order::count());
    }

    public function test_stock_running_out_after_the_checkout_page_loaded_blocks_order_creation(): void
    {
        $part = Part::factory()->create(['name' => 'Brake Rotor', 'stock_quantity' => 3, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part, 3);

        $part->update(['stock_quantity' => 0]);

        $response = $this->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $response->assertOk();
        $response->assertSee('no longer available');
        $this->assertSame(0, Order::count());
    }

    public function test_checking_out_the_last_unit_reserves_it_so_it_cannot_be_added_to_another_cart(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 1, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part, 1);

        $checkout = $this->post(route('checkout.store'), $this->validCheckoutPayload($token));
        $checkout->assertRedirect(route('orders.show', Order::first()));

        // The cart is empty again (checkout cleared it) — a fresh attempt
        // to buy the same unit, which Order::first() now holds a stock
        // reservation on, must be rejected rather than oversold.
        $response = $this->post(route('cart.items.store', $part), ['quantity' => 1]);

        $response->assertSessionHasErrors('quantity');
        $this->assertEmpty(session('cart', []));
    }

    public function test_reviewing_a_changed_order_and_resubmitting_succeeds(): void
    {
        $part = Part::factory()->create(['base_price_cents' => 5000, 'stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part);
        $part->update(['base_price_cents' => 7500]);

        $blocked = $this->post(route('checkout.store'), $this->validCheckoutPayload($token));
        $newToken = $blocked->viewData('checkoutToken');

        $this->assertNotSame($token, $newToken);

        $response = $this->post(route('checkout.store'), $this->validCheckoutPayload($newToken));

        $this->assertSame(1, Order::count());
        $response->assertRedirect(route('orders.show', Order::first()));
        $this->assertSame(7500, Order::first()->items->first()->unit_price_cents);
    }

    public function test_duplicate_submission_of_the_same_checkout_does_not_create_a_second_order(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part);
        $payload = $this->validCheckoutPayload($token);

        $first = $this->post(route('checkout.store'), $payload);
        $this->assertSame(1, Order::count());

        // Simulate a double-click / retried request / browser back+resubmit:
        // cart is already empty at this point, matching a real resubmission.
        $second = $this->post(route('checkout.store'), $payload);

        $this->assertSame(1, Order::count());
        $first->assertRedirect(route('orders.show', Order::first()));
        $second->assertRedirect(route('orders.show', Order::first()));
    }

    private function addToCartAndStartCheckout(Part $part, int $quantity = 1): string
    {
        $this->post(route('cart.items.store', $part), ['quantity' => $quantity]);
        $response = $this->get(route('checkout.create'));

        return $response->viewData('checkoutToken');
    }

    /**
     * @return array<string, mixed>
     */
    private function validCheckoutPayload(string $token, array $overrides = []): array
    {
        return array_merge([
            'checkout_token' => $token,
            'email' => 'buyer@example.com',
            'shipping_name' => 'Jane Mechanic',
            'shipping_line1' => '123 Garage Row',
            'shipping_city' => 'Springfield',
            'shipping_state' => 'IL',
            'shipping_postal_code' => '62704',
            'shipping_country' => 'US',
        ], $overrides);
    }
}
