<?php

namespace Tests\Unit;

use App\Enums\PartStatus;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exercises Part::search() (Laravel Scout — see config/scout.php) directly
 * against the model/query layer, bypassing the storefront controller and
 * views entirely. That keeps this independent of anything unrelated the
 * rendered page might need (e.g. this environment's missing intl
 * extension, which blocks Number::currency() in the part-card component).
 */
class PartSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_by_name(): void
    {
        Part::factory()->create(['name' => 'Rear Brake Pad Set']);
        Part::factory()->create(['name' => 'Cabin Air Filter']);

        $results = Part::search('Brake Pad')->get();

        $this->assertTrue($results->contains(fn (Part $part) => $part->name === 'Rear Brake Pad Set'));
        $this->assertFalse($results->contains(fn (Part $part) => $part->name === 'Cabin Air Filter'));
    }

    public function test_search_matches_by_sku(): void
    {
        Part::factory()->create(['name' => 'Rear Brake Pad Set', 'sku' => 'BR-99999']);
        Part::factory()->create(['name' => 'Cabin Air Filter', 'sku' => 'CA-11111']);

        $results = Part::search('BR-99999')->get();

        $this->assertCount(1, $results);
        $this->assertSame('BR-99999', $results->first()->sku);
    }

    public function test_search_matches_by_description(): void
    {
        Part::factory()->create(['name' => 'Widget', 'description' => 'Fits a 2015 Toyota Camry perfectly.']);
        Part::factory()->create(['name' => 'Other Widget', 'description' => 'A generic replacement part.']);

        $results = Part::search('Camry')->get();

        $this->assertCount(1, $results);
        $this->assertSame('Widget', $results->first()->name);
    }

    public function test_search_excludes_draft_and_discontinued_parts(): void
    {
        Part::factory()->create(['name' => 'Active Widget', 'status' => PartStatus::Active]);
        Part::factory()->create(['name' => 'Draft Widget', 'status' => PartStatus::Draft]);
        Part::factory()->create(['name' => 'Discontinued Widget', 'status' => PartStatus::Discontinued]);

        $results = Part::search('Widget')->where('status', PartStatus::Active->value)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Active Widget', $results->first()->name);
    }

    public function test_search_can_be_filtered_by_category(): void
    {
        $brakes = Category::factory()->create();
        $filters = Category::factory()->create();
        Part::factory()->create(['name' => 'Brake Widget', 'category_id' => $brakes->id]);
        Part::factory()->create(['name' => 'Filter Widget', 'category_id' => $filters->id]);

        $results = Part::search('Widget')->where('category_id', $brakes->id)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Brake Widget', $results->first()->name);
    }

    public function test_search_can_be_filtered_by_manufacturer(): void
    {
        $bosch = Manufacturer::factory()->create();
        $acdelco = Manufacturer::factory()->create();
        Part::factory()->create(['name' => 'Bosch Widget', 'manufacturer_id' => $bosch->id]);
        Part::factory()->create(['name' => 'ACDelco Widget', 'manufacturer_id' => $acdelco->id]);

        $results = Part::search('Widget')->where('manufacturer_id', $bosch->id)->get();

        $this->assertCount(1, $results);
        $this->assertSame('Bosch Widget', $results->first()->name);
    }

    public function test_search_results_can_be_sorted_by_price(): void
    {
        Part::factory()->create(['name' => 'Widget Cheap', 'base_price_cents' => 1000]);
        Part::factory()->create(['name' => 'Widget Expensive', 'base_price_cents' => 5000]);

        $results = Part::search('Widget')->orderBy('base_price_cents', 'asc')->get();

        $this->assertSame(['Widget Cheap', 'Widget Expensive'], $results->pluck('name')->all());
    }

    public function test_a_draft_part_is_not_searchable_even_without_an_explicit_status_filter(): void
    {
        Part::factory()->create(['name' => 'Widget', 'status' => PartStatus::Draft]);

        $this->assertFalse(Part::first()->shouldBeSearchable());
    }
}
