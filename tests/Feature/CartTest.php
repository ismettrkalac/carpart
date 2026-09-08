<?php

namespace Tests\Feature;

use App\Enums\PartStatus;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_cart_shows_empty_state(): void
    {
        $response = $this->get(route('cart.index'));

        $response->assertOk();
        $response->assertSee('Your cart is empty.');
    }

    public function test_a_product_can_be_added_to_the_cart(): void
    {
        $part = Part::factory()->create(['name' => 'Oil Filter', 'stock_quantity' => 10, 'status' => PartStatus::Active]);

        $response = $this->post(route('cart.items.store', $part), ['quantity' => 2]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $cart = $this->get(route('cart.index'));
        $cart->assertSee('Oil Filter');
        $cart->assertSee('2', false);
    }

    public function test_adding_the_same_product_twice_accumulates_quantity(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);

        $this->post(route('cart.items.store', $part), ['quantity' => 2]);
        $this->post(route('cart.items.store', $part), ['quantity' => 3]);

        $this->assertSame(5, session('cart')[$part->id]);
    }

    public function test_adding_a_zero_quantity_is_rejected(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);

        $response = $this->post(route('cart.items.store', $part), ['quantity' => 0]);

        $response->assertSessionHasErrors('quantity');
        $this->assertEmpty(session('cart', []));
    }

    public function test_adding_a_negative_quantity_is_rejected(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);

        $response = $this->post(route('cart.items.store', $part), ['quantity' => -1]);

        $response->assertSessionHasErrors('quantity');
        $this->assertEmpty(session('cart', []));
    }

    public function test_adding_a_non_integer_quantity_is_rejected(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);

        $response = $this->post(route('cart.items.store', $part), ['quantity' => 'two']);

        $response->assertSessionHasErrors('quantity');
    }

    public function test_adding_a_draft_product_is_rejected(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Draft]);

        $response = $this->post(route('cart.items.store', $part), ['quantity' => 1]);

        $response->assertSessionHasErrors('quantity');
        $this->assertEmpty(session('cart', []));
    }

    public function test_adding_an_out_of_stock_product_is_rejected(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 0, 'status' => PartStatus::Active]);

        $response = $this->post(route('cart.items.store', $part), ['quantity' => 1]);

        $response->assertSessionHasErrors('quantity');
        $this->assertEmpty(session('cart', []));
    }

    public function test_adding_more_than_available_stock_is_rejected(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 3, 'status' => PartStatus::Active]);

        $response = $this->post(route('cart.items.store', $part), ['quantity' => 5]);

        $response->assertSessionHasErrors('quantity');
        $this->assertEmpty(session('cart', []));
    }

    public function test_quantity_can_be_updated(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 1]);

        $response = $this->patch(route('cart.items.update', $part), ['quantity' => 4]);

        $response->assertRedirect();
        $this->assertSame(4, session('cart')[$part->id]);
    }

    public function test_quantity_can_be_updated_via_json_and_returns_a_summary(): void
    {
        $part = Part::factory()->create(['base_price_cents' => 5000, 'stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 1]);

        $response = $this->patchJson(route('cart.items.update', $part), ['quantity' => 4]);

        $response->assertOk();
        $response->assertJson([
            'isEmpty' => false,
            'cartCount' => 4,
            'subtotalCents' => 20000,
            'item' => [
                'lineTotalCents' => 20000,
                'needsAttention' => false,
            ],
        ]);
        $this->assertSame(4, session('cart')[$part->id]);
    }

    public function test_updating_beyond_stock_via_json_returns_an_error_without_changing_quantity(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 5, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 2]);

        $response = $this->patchJson(route('cart.items.update', $part), ['quantity' => 99]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
        $this->assertSame(2, session('cart')[$part->id]);
    }

    public function test_updating_beyond_available_stock_is_rejected_and_leaves_quantity_unchanged(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 5, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 2]);

        $response = $this->patch(route('cart.items.update', $part), ['quantity' => 99]);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame(2, session('cart')[$part->id]);
    }

    public function test_an_item_can_be_removed(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 1]);

        $response = $this->delete(route('cart.items.destroy', $part->id));

        $response->assertRedirect();
        $this->assertArrayNotHasKey($part->id, session('cart', []));
    }

    public function test_an_item_can_be_removed_via_json_and_returns_a_summary(): void
    {
        $part = Part::factory()->create(['base_price_cents' => 3000, 'stock_quantity' => 10, 'status' => PartStatus::Active]);
        $other = Part::factory()->create(['base_price_cents' => 1000, 'stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 1]);
        $this->post(route('cart.items.store', $other), ['quantity' => 1]);

        $response = $this->deleteJson(route('cart.items.destroy', $part->id));

        $response->assertOk();
        $response->assertJson(['isEmpty' => false, 'subtotalCents' => 1000]);
        $this->assertArrayNotHasKey($part->id, session('cart', []));
    }

    public function test_removing_the_last_item_via_json_reports_the_cart_as_empty(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 1]);

        $response = $this->deleteJson(route('cart.items.destroy', $part->id));

        $response->assertOk();
        $response->assertJson(['isEmpty' => true, 'subtotalCents' => 0]);
    }

    public function test_removing_an_item_whose_part_was_deleted_still_works(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 1]);
        $partId = $part->id;
        $part->delete();

        $response = $this->delete(route('cart.items.destroy', $partId));

        $response->assertRedirect();
        $this->assertArrayNotHasKey($partId, session('cart', []));
    }

    public function test_cart_page_flags_a_line_whose_part_was_deleted_after_adding(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 1]);
        $part->delete();

        $response = $this->get(route('cart.index'));

        $response->assertOk();
        $response->assertSee('no longer available');
    }

    public function test_cart_page_flags_a_line_that_now_exceeds_current_stock(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 5, 'status' => PartStatus::Active]);
        $this->post(route('cart.items.store', $part), ['quantity' => 5]);
        $part->update(['stock_quantity' => 2]);

        $response = $this->get(route('cart.index'));

        $response->assertOk();
        $response->assertSee('Only 2 available');
    }

    public function test_price_and_quantity_cannot_be_tampered_with_from_the_request(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active, 'base_price_cents' => 5000]);

        // Attempting to inject a fake price/line-total has no effect —
        // the server only ever reads price from the database.
        $this->post(route('cart.items.store', $part), [
            'quantity' => 1,
            'unit_price_cents' => 1,
            'line_total_cents' => 1,
        ]);

        $response = $this->get(route('cart.index'));
        $response->assertSee('$50.00');
        $response->assertDontSee('$0.01');
    }

    public function test_adding_to_the_cart_does_not_change_stock(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10, 'status' => PartStatus::Active]);

        $this->post(route('cart.items.store', $part), ['quantity' => 4]);

        $this->assertSame(10, $part->fresh()->stock_quantity);
    }
}
