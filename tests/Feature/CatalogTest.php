<?php

namespace Tests\Feature;

use App\Enums\PartStatus;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_lists_categories_and_recent_parts(): void
    {
        $category = Category::factory()->create(['name' => 'Brakes']);
        Part::factory()->create(['name' => 'Front Brake Rotor', 'category_id' => $category->id, 'stock_quantity' => 5]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Brakes');
        $response->assertSee('Front Brake Rotor');
    }

    public function test_parts_index_only_lists_published_parts(): void
    {
        Part::factory()->create(['name' => 'Active Oil Filter', 'status' => PartStatus::Active]);
        Part::factory()->create(['name' => 'Draft Oil Filter', 'status' => PartStatus::Draft]);
        Part::factory()->create(['name' => 'Discontinued Oil Filter', 'status' => PartStatus::Discontinued]);

        $response = $this->get('/parts');

        $response->assertOk();
        $response->assertSee('Active Oil Filter');
        $response->assertDontSee('Draft Oil Filter');
        $response->assertDontSee('Discontinued Oil Filter');
    }

    public function test_parts_index_can_search_by_name(): void
    {
        Part::factory()->create(['name' => 'Rear Brake Pad Set']);
        Part::factory()->create(['name' => 'Cabin Air Filter']);

        $response = $this->get('/parts?q=Brake+Pad');

        $response->assertOk();
        $response->assertSee('Rear Brake Pad Set');
        $response->assertDontSee('Cabin Air Filter');
    }

    public function test_parts_index_can_search_by_sku(): void
    {
        Part::factory()->create(['name' => 'Rear Brake Pad Set', 'sku' => 'BR-99999']);
        Part::factory()->create(['name' => 'Cabin Air Filter', 'sku' => 'CA-11111']);

        $response = $this->get('/parts?q=BR-99999');

        $response->assertOk();
        $response->assertSee('Rear Brake Pad Set');
        $response->assertDontSee('Cabin Air Filter');
    }

    public function test_parts_index_can_filter_by_manufacturer(): void
    {
        $bosch = Manufacturer::factory()->create(['name' => 'Bosch', 'slug' => 'bosch']);
        $acdelco = Manufacturer::factory()->create(['name' => 'ACDelco', 'slug' => 'acdelco']);

        Part::factory()->create(['name' => 'Bosch Alternator', 'manufacturer_id' => $bosch->id]);
        Part::factory()->create(['name' => 'ACDelco Battery', 'manufacturer_id' => $acdelco->id]);

        $response = $this->get('/parts?manufacturer=bosch');

        $response->assertOk();
        $response->assertSee('Bosch Alternator');
        $response->assertDontSee('ACDelco Battery');
    }

    public function test_category_page_only_shows_parts_in_that_category(): void
    {
        $brakes = Category::factory()->create(['name' => 'Brakes', 'slug' => 'brakes']);
        $filters = Category::factory()->create(['name' => 'Filters', 'slug' => 'filters']);

        Part::factory()->create(['name' => 'Front Brake Rotor', 'category_id' => $brakes->id]);
        Part::factory()->create(['name' => 'Oil Filter', 'category_id' => $filters->id]);

        $response = $this->get('/categories/brakes');

        $response->assertOk();
        $response->assertSee('Front Brake Rotor');
        $response->assertDontSee('Oil Filter');
    }

    public function test_parts_index_paginates_results(): void
    {
        // Explicit, descending created_at so "newest first" ordering is deterministic:
        // Test Part 0 is newest (page 1), Test Part 12 is oldest (page 2).
        Part::factory(13)->sequence(fn ($sequence) => [
            'name' => "Test Part {$sequence->index}",
            'created_at' => now()->subMinutes($sequence->index),
        ])->create();

        $response = $this->get('/parts');

        $response->assertOk();
        $response->assertSee('Test Part 0');
        $response->assertDontSee('Test Part 12');

        $secondPage = $this->get('/parts?page=2');
        $secondPage->assertOk();
        $secondPage->assertSee('Test Part 12');
    }

    public function test_part_page_shows_details_and_fitments_with_a_disclaimer(): void
    {
        $part = Part::factory()->create([
            'name' => 'Front Brake Rotor',
            'description' => 'A great rotor.',
        ]);
        $part->fitments()->create([
            'make' => 'Toyota',
            'model' => 'Camry',
            'year_start' => 2010,
            'year_end' => 2015,
            'engine' => '2.5L I4',
            'trim' => 'LE',
        ]);

        $response = $this->get(route('parts.show', $part));

        $response->assertOk();
        $response->assertSee('Front Brake Rotor');
        $response->assertSee('A great rotor.');
        $response->assertSee('Toyota');
        $response->assertSee('Camry');
        $response->assertSee('not verified part compatibility');
    }

    public function test_part_page_returns_404_for_a_non_published_part(): void
    {
        $part = Part::factory()->create(['status' => PartStatus::Draft]);

        $response = $this->get(route('parts.show', $part));

        $response->assertNotFound();
    }

    public function test_part_page_shows_related_parts_from_the_same_category(): void
    {
        $category = Category::factory()->create();
        $part = Part::factory()->create(['category_id' => $category->id, 'name' => 'Main Part']);
        $related = Part::factory()->create(['category_id' => $category->id, 'name' => 'Related Part']);
        $unrelated = Part::factory()->create(['name' => 'Unrelated Part']);

        $response = $this->get(route('parts.show', $part));

        $response->assertOk();
        $response->assertSee('Related Part');
        $response->assertDontSee('Unrelated Part');
    }
}
