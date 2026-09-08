<?php

namespace Tests\Feature;

use App\Enums\PartStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayseraCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_falls_back_to_the_receipt_page_when_paysera_is_not_configured(): void
    {
        config(['services.paysera.client_id' => '', 'services.paysera.client_secret' => '']);
        Http::fake();

        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part);

        $response = $this->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $order = Order::first();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertNull($order->payment_reference);
        Http::assertNothingSent();
    }

    public function test_checkout_redirects_to_a_paysera_payment_link_when_configured(): void
    {
        $this->fakePayseraConfig();
        $this->fakePayseraApi();

        $part = Part::factory()->create(['base_price_cents' => 5000, 'stock_quantity' => 10, 'status' => PartStatus::Active]);
        $token = $this->addToCartAndStartCheckout($part, 2);

        $response = $this->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $response->assertRedirect('https://bank.paysera.com/pay/abc123');

        $order = Order::first();
        $this->assertSame('paysera', $order->payment_provider);
        $this->assertSame('order-uuid-123', $order->payment_reference);
        $this->assertSame(PaymentStatus::PendingPayment, $order->payment_status);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.paysera.com/merchant-order/integration/v1/orders'
            && $request['purchase']['reference'] === $order->uuid
            && $request['purchase']['amount'] === $order->total_cents
            && $request['purchase']['currency'] === $order->currency);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.paysera.com/checkout-payment-link/integration/v1/payment-links'
            && $request['order_id'] === 'order-uuid-123');
    }

    public function test_the_access_token_is_reused_across_requests(): void
    {
        $this->fakePayseraConfig();
        $this->fakePayseraApi();

        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $tokenA = $this->addToCartAndStartCheckout($part);
        $this->post(route('checkout.store'), $this->validCheckoutPayload($tokenA));

        $part2 = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $tokenB = $this->addToCartAndStartCheckout($part2);
        $this->post(route('checkout.store'), $this->validCheckoutPayload($tokenB, ['email' => 'second@example.com']));

        $tokenRequests = Http::recorded(fn ($request) => str_contains($request->url(), 'openid-connect/token'));
        $this->assertCount(1, $tokenRequests);
    }

    public function test_a_pending_order_can_restart_a_paysera_session_from_the_receipt_page(): void
    {
        $this->fakePayseraConfig();
        $this->fakePayseraApi();
        $order = Order::factory()->create(['user_id' => null]);

        $response = $this->post(route('orders.pay', $order));

        $response->assertRedirect('https://bank.paysera.com/pay/abc123');
        $this->assertSame('order-uuid-123', $order->fresh()->payment_reference);
    }

    public function test_a_paid_order_cannot_be_paid_again(): void
    {
        $this->fakePayseraConfig();
        $order = Order::factory()->create(['user_id' => null]);
        $order->forceFill(['payment_status' => PaymentStatus::Paid])->save();
        Http::fake();

        $response = $this->post(route('orders.pay', $order));

        $response->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_pay_is_unavailable_when_paysera_is_not_configured(): void
    {
        config(['services.paysera.client_id' => '', 'services.paysera.client_secret' => '']);
        $order = Order::factory()->create(['user_id' => null]);

        $response = $this->post(route('orders.pay', $order));

        $response->assertNotFound();
    }

    public function test_a_different_customer_cannot_restart_payment_on_someone_elses_order(): void
    {
        $this->fakePayseraConfig();
        $owner = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);
        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->post(route('orders.pay', $order));

        $response->assertForbidden();
    }

    private function fakePayseraConfig(): void
    {
        config([
            'services.paysera.client_id' => 'test_client_id',
            'services.paysera.client_secret' => 'test_client_secret',
        ]);
    }

    private function fakePayseraApi(): void
    {
        Http::fake([
            '*openid-connect/token' => Http::response([
                'access_token' => 'fake_access_token',
                'expires_in' => 3600,
            ]),
            'api.paysera.com/merchant-order/*' => Http::response([
                'order_id' => 'order-uuid-123',
            ]),
            'api.paysera.com/checkout-payment-link/*' => Http::response([
                'payment_URL' => 'https://bank.paysera.com/pay/abc123',
            ]),
        ]);
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
