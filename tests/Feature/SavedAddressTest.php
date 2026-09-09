<?php

namespace Tests\Feature;

use App\Models\SavedAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('account.addresses.index'));

        $response->assertRedirect(route('login.create'));
    }

    public function test_a_customer_can_view_their_saved_addresses(): void
    {
        $user = User::factory()->create();
        SavedAddress::factory()->for($user)->create(['label' => 'Home']);

        $response = $this->actingAs($user)->get(route('account.addresses.index'));

        $response->assertOk();
        $response->assertSee('Home');
    }

    public function test_a_customer_does_not_see_another_customers_addresses(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        SavedAddress::factory()->for($other)->create(['label' => 'Not Mine']);

        $response = $this->actingAs($user)->get(route('account.addresses.index'));

        $response->assertOk();
        $response->assertDontSee('Not Mine');
    }

    public function test_a_customer_can_add_an_address(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('account.addresses.store'), $this->validAddressPayload());

        $response->assertRedirect(route('account.addresses.index'));
        $this->assertDatabaseHas('saved_addresses', [
            'user_id' => $user->id,
            'name' => 'Jane Mechanic',
            'city' => 'Springfield',
        ]);
    }

    public function test_the_first_address_becomes_the_default_automatically(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.addresses.store'), $this->validAddressPayload());

        $this->assertTrue($user->savedAddresses()->first()->is_default);
    }

    public function test_marking_a_new_address_default_replaces_the_previous_default(): void
    {
        $user = User::factory()->create();
        $first = SavedAddress::factory()->for($user)->default()->create();

        $this->actingAs($user)->post(route('account.addresses.store'), [
            ...$this->validAddressPayload(),
            'is_default' => '1',
        ]);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($user->savedAddresses()->latest('id')->first()->is_default);
    }

    public function test_adding_an_address_with_missing_required_fields_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('account.addresses.store'), []);

        $response->assertSessionHasErrors(['name', 'line1', 'city', 'state', 'postal_code', 'country']);
        $this->assertSame(0, SavedAddress::count());
    }

    public function test_a_customer_can_update_their_address(): void
    {
        $user = User::factory()->create();
        $address = SavedAddress::factory()->for($user)->create(['city' => 'Old City']);

        $response = $this->actingAs($user)->put(route('account.addresses.update', $address), [
            ...$this->validAddressPayload(),
            'city' => 'New City',
        ]);

        $response->assertRedirect(route('account.addresses.index'));
        $this->assertSame('New City', $address->fresh()->city);
    }

    public function test_a_customer_cannot_update_another_customers_address(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $address = SavedAddress::factory()->for($other)->create();

        $response = $this->actingAs($user)->put(route('account.addresses.update', $address), $this->validAddressPayload());

        $response->assertNotFound();
    }

    public function test_a_customer_cannot_view_the_edit_form_for_another_customers_address(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $address = SavedAddress::factory()->for($other)->create();

        $response = $this->actingAs($user)->get(route('account.addresses.edit', $address));

        $response->assertNotFound();
    }

    public function test_a_customer_can_delete_an_address(): void
    {
        $user = User::factory()->create();
        $address = SavedAddress::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('account.addresses.destroy', $address));

        $response->assertRedirect(route('account.addresses.index'));
        $this->assertModelMissing($address);
    }

    public function test_deleting_the_default_address_promotes_the_next_one(): void
    {
        $user = User::factory()->create();
        $default = SavedAddress::factory()->for($user)->default()->create();
        $other = SavedAddress::factory()->for($user)->create();

        $this->actingAs($user)->delete(route('account.addresses.destroy', $default));

        $this->assertTrue($other->fresh()->is_default);
    }

    public function test_a_customer_cannot_delete_another_customers_address(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $address = SavedAddress::factory()->for($other)->create();

        $response = $this->actingAs($user)->delete(route('account.addresses.destroy', $address));

        $response->assertNotFound();
        $this->assertModelExists($address);
    }

    /**
     * @return array<string, string>
     */
    private function validAddressPayload(): array
    {
        return [
            'label' => 'Home',
            'name' => 'Jane Mechanic',
            'line1' => '123 Garage Row',
            'city' => 'Springfield',
            'state' => 'IL',
            'postal_code' => '62704',
            'country' => 'US',
        ];
    }
}
