<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SavedAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('account.profile'));

        $response->assertRedirect(route('login.create'));
    }

    public function test_it_shows_the_customers_name_and_email(): void
    {
        $user = User::factory()->create(['name' => 'Jane Mechanic', 'email' => 'jane@example.com']);

        $response = $this->actingAs($user)->get(route('account.profile'));

        $response->assertOk();
        $response->assertSee('Jane Mechanic');
        $response->assertSee('jane@example.com');
    }

    public function test_it_shows_addresses_and_orders_as_separate_categories_to_choose_from(): void
    {
        $user = User::factory()->create();
        SavedAddress::factory()->count(2)->for($user)->create();
        Order::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.profile'));

        $response->assertOk();
        $response->assertSee('Addresses');
        $response->assertSee('2 addresses saved');
        $response->assertSee('Orders');
        $response->assertSee('3 orders placed');
        $response->assertSee(route('account.addresses.index'), false);
        $response->assertSee(route('account.orders.index'), false);
    }

    public function test_it_uses_singular_wording_for_exactly_one_of_each(): void
    {
        $user = User::factory()->create();
        SavedAddress::factory()->for($user)->create();
        Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.profile'));

        $response->assertOk();
        $response->assertSee('1 address saved');
        $response->assertSee('1 order placed');
    }

    public function test_it_only_counts_the_customers_own_addresses_and_orders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        SavedAddress::factory()->count(2)->for($other)->create();
        Order::factory()->count(2)->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->get(route('account.profile'));

        $response->assertOk();
        $response->assertSee('0 addresses saved');
        $response->assertSee('0 orders placed');
    }

    public function test_logging_in_redirects_to_the_profile_page(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('account.profile'));
    }

    public function test_registering_redirects_to_the_profile_page(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Jane Mechanic',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('account.profile'));
    }
}
