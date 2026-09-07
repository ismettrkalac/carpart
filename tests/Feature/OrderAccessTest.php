<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_order_is_viewable_via_its_uuid(): void
    {
        $order = Order::factory()->create(['user_id' => null]);

        $response = $this->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('Order received. Payment has not been collected.');
    }

    public function test_an_unknown_order_uuid_returns_404(): void
    {
        $response = $this->get('/orders/'.Str::uuid());

        $response->assertNotFound();
    }

    public function test_the_owning_user_can_view_their_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertOk();
    }

    public function test_a_different_authenticated_user_cannot_view_someone_elses_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($other)->get(route('orders.show', $order));

        $response->assertForbidden();
    }

    public function test_a_guest_visitor_cannot_view_an_authenticated_users_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $response = $this->get(route('orders.show', $order));

        $response->assertForbidden();
    }
}
