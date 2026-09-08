<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Policies\OrderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Laravel\Models\MoonshineUser;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_redirected_away_from_the_admin_panel(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect();
        $this->assertStringContainsString('/admin/login', $response->headers->get('Location'));
    }

    public function test_a_logged_in_customer_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        // The 'web' guard session doesn't grant the separate 'moonshine'
        // guard anything — still redirected to the admin login.
        $response->assertRedirect();
        $this->assertStringContainsString('/admin/login', $response->headers->get('Location'));
    }

    public function test_an_authenticated_staff_member_can_view_the_dashboard(): void
    {
        $staff = MoonshineUser::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')->get('/admin');

        $response->assertOk();
    }

    public function test_an_authenticated_staff_member_can_view_the_orders_index(): void
    {
        $staff = MoonshineUser::factory()->create();
        Order::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')
            ->get('/admin/resource/order-resource/order-index-page');

        $response->assertOk();
    }

    public function test_an_authenticated_staff_member_can_view_an_order_detail_page(): void
    {
        $staff = MoonshineUser::factory()->create();
        $order = Order::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')
            ->get("/admin/resource/order-resource/order-detail-page/{$order->id}");

        $response->assertOk();
        $response->assertSee($order->orderNumber());
    }

    public function test_order_deletion_is_denied_by_policy(): void
    {
        $staff = MoonshineUser::factory()->create();
        $order = Order::factory()->create();

        $policy = new OrderPolicy;

        $this->assertFalse($policy->delete($staff, $order));
        $this->assertFalse($policy->massDelete($staff));
        $this->assertFalse($policy->forceDelete($staff, $order));
    }

    public function test_ad_hoc_order_creation_is_denied_by_policy(): void
    {
        $staff = MoonshineUser::factory()->create();

        $this->assertFalse((new OrderPolicy)->create($staff));
    }
}
