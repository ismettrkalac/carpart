<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_the_account_orders_index(): void
    {
        $response = $this->get(route('account.orders.index'));

        $response->assertRedirect(route('login.create'));
    }

    public function test_a_customer_sees_only_their_own_orders(): void
    {
        $user = User::factory()->create();
        $mine = Order::factory()->create(['user_id' => $user->id]);
        $someoneElses = Order::factory()->create();

        $response = $this->actingAs($user)->get(route('account.orders.index'));

        $response->assertOk();
        $response->assertSee($mine->orderNumber());
        $response->assertDontSee($someoneElses->orderNumber());
    }

    public function test_a_customer_can_view_their_own_order_detail(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.orders.show', $order));

        $response->assertOk();
        $response->assertSee($order->orderNumber());
    }

    public function test_a_customer_cannot_view_another_customers_order_via_the_account_area(): void
    {
        $user = User::factory()->create();
        $othersOrder = Order::factory()->create();

        $response = $this->actingAs($user)->get(route('account.orders.show', $othersOrder));

        $response->assertNotFound();
    }

    public function test_a_guest_order_is_not_reachable_via_the_account_area_even_when_logged_in(): void
    {
        $user = User::factory()->create();
        $guestOrder = Order::factory()->create(['user_id' => null]);

        $response = $this->actingAs($user)->get(route('account.orders.show', $guestOrder));

        $response->assertNotFound();
    }

    public function test_changing_the_order_id_in_the_url_never_exposes_another_customers_data(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $othersOrder = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/account/orders/{$othersOrder->uuid}");
        $response->assertNotFound();

        // A random, non-existent UUID is likewise just a 404, not an error
        // that would reveal whether an order exists.
        $randomResponse = $this->actingAs($user)->get('/account/orders/'.Str::uuid());
        $randomResponse->assertNotFound();
    }

    public function test_guests_cannot_view_an_order_via_the_account_area_at_all(): void
    {
        $order = Order::factory()->create();

        $response = $this->get(route('account.orders.show', $order));

        $response->assertRedirect(route('login.create'));
    }
}
