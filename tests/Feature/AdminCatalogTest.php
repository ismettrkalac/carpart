<?php

namespace Tests\Feature;

use App\Enums\PartStatus;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Part;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use MoonShine\Laravel\Models\MoonshineUser;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_cannot_access_the_part_catalog_admin(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get('/admin/resource/part-resource/part-index-page');

        $response->assertRedirect();
        $this->assertStringContainsString('/admin/login', $response->headers->get('Location'));
    }

    public function test_staff_can_view_the_part_index_page(): void
    {
        $staff = MoonshineUser::factory()->create();
        Part::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')
            ->get('/admin/resource/part-resource/part-index-page');

        $response->assertOk();
    }

    public function test_staff_can_create_a_part_with_valid_data(): void
    {
        $staff = MoonshineUser::factory()->create();
        $manufacturer = Manufacturer::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')->post('/admin/resource/part-resource/crud', [
            'manufacturer_id' => $manufacturer->id,
            'category_id' => $category->id,
            'sku' => 'BR-12345',
            'name' => 'Front Brake Rotor',
            'slug' => 'front-brake-rotor',
            'description' => 'A great rotor.',
            'status' => PartStatus::Active->value,
            'base_price_cents' => 4999,
            'currency' => 'USD',
            'stock_quantity' => 25,
            'weight_kg' => 3.5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('parts', [
            'sku' => 'BR-12345',
            'name' => 'Front Brake Rotor',
            'manufacturer_id' => $manufacturer->id,
            'category_id' => $category->id,
            'status' => 'active',
            'base_price_cents' => 4999,
            'stock_quantity' => 25,
        ]);
    }

    public function test_creating_a_part_with_a_duplicate_sku_is_rejected(): void
    {
        $staff = MoonshineUser::factory()->create();
        Part::factory()->create(['sku' => 'BR-12345']);

        $response = $this->actingAs($staff, 'moonshine')->post('/admin/resource/part-resource/crud', [
            'sku' => 'BR-12345',
            'name' => 'Another Rotor',
            'slug' => 'another-rotor',
            'status' => PartStatus::Active->value,
            'base_price_cents' => 1000,
            'currency' => 'USD',
            'stock_quantity' => 1,
        ]);

        $response->assertSessionHasErrors('sku', errorBag: 'part-resource');
        $this->assertDatabaseMissing('parts', ['name' => 'Another Rotor']);
    }

    public function test_creating_a_part_with_missing_required_fields_is_rejected(): void
    {
        $staff = MoonshineUser::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')->post('/admin/resource/part-resource/crud', []);

        $response->assertSessionHasErrors(
            ['sku', 'name', 'slug', 'status', 'base_price_cents', 'currency', 'stock_quantity'],
            errorBag: 'part-resource',
        );
        $this->assertSame(0, Part::count());
    }

    public function test_staff_can_update_a_part(): void
    {
        $staff = MoonshineUser::factory()->create();
        $part = Part::factory()->create(['name' => 'Old Name', 'stock_quantity' => 5]);

        $response = $this->actingAs($staff, 'moonshine')->put("/admin/resource/part-resource/crud/{$part->id}", [
            'sku' => $part->sku,
            'name' => 'New Name',
            'slug' => $part->slug,
            'status' => $part->status->value,
            'base_price_cents' => $part->base_price_cents,
            'currency' => $part->currency,
            'stock_quantity' => 12,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('parts', ['id' => $part->id, 'name' => 'New Name', 'stock_quantity' => 12]);
    }

    public function test_staff_can_add_an_image_to_a_part(): void
    {
        Storage::fake('public');
        $staff = MoonshineUser::factory()->create();
        $part = Part::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')->post('/admin/resource/part-image-resource/crud', [
            'part_id' => $part->id,
            'path' => UploadedFile::fake()->image('rotor.jpg'),
            'position' => 0,
        ]);

        $response->assertRedirect();
        $this->assertSame(1, $part->images()->count());
        Storage::disk('public')->assertExists($part->images()->first()->path);
    }

    public function test_adding_a_part_image_without_a_file_is_rejected(): void
    {
        $staff = MoonshineUser::factory()->create();
        $part = Part::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')->post('/admin/resource/part-image-resource/crud', [
            'part_id' => $part->id,
            'position' => 0,
        ]);

        $response->assertSessionHasErrors('path', errorBag: 'part-image-resource');
        $this->assertSame(0, $part->images()->count());
    }

    public function test_staff_can_view_the_category_index_page(): void
    {
        $staff = MoonshineUser::factory()->create();
        Category::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')
            ->get('/admin/resource/category-resource/category-index-page');

        $response->assertOk();
    }

    public function test_staff_can_create_a_category(): void
    {
        $staff = MoonshineUser::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')->post('/admin/resource/category-resource/crud', [
            'name' => 'Brakes',
            'slug' => 'brakes',
            'position' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Brakes', 'slug' => 'brakes']);
    }

    public function test_creating_a_category_with_a_duplicate_slug_is_rejected(): void
    {
        $staff = MoonshineUser::factory()->create();
        Category::factory()->create(['slug' => 'brakes']);

        $response = $this->actingAs($staff, 'moonshine')->post('/admin/resource/category-resource/crud', [
            'name' => 'Brakes Again',
            'slug' => 'brakes',
        ]);

        $response->assertSessionHasErrors('slug', errorBag: 'category-resource');
        $this->assertDatabaseMissing('categories', ['name' => 'Brakes Again']);
    }

    public function test_a_category_cannot_be_set_as_its_own_parent(): void
    {
        $staff = MoonshineUser::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')->put("/admin/resource/category-resource/crud/{$category->id}", [
            'parent_id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ]);

        $response->assertSessionHasErrors('parent_id', errorBag: 'category-resource');
    }

    public function test_deleting_a_category_leaves_its_parts_uncategorized(): void
    {
        $staff = MoonshineUser::factory()->create();
        $category = Category::factory()->create();
        $part = Part::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($staff, 'moonshine')->delete("/admin/resource/category-resource/crud/{$category->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('parts', ['id' => $part->id, 'category_id' => null]);
    }

    public function test_staff_can_view_the_manufacturer_index_page(): void
    {
        $staff = MoonshineUser::factory()->create();
        Manufacturer::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')
            ->get('/admin/resource/manufacturer-resource/manufacturer-index-page');

        $response->assertOk();
    }

    public function test_staff_can_create_a_manufacturer(): void
    {
        $staff = MoonshineUser::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')->post('/admin/resource/manufacturer-resource/crud', [
            'name' => 'Bosch',
            'slug' => 'bosch',
            'logo_path' => 'https://example.com/bosch.png',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('manufacturers', ['name' => 'Bosch', 'slug' => 'bosch']);
    }

    public function test_staff_can_view_the_supplier_index_page(): void
    {
        $staff = MoonshineUser::factory()->create();
        Supplier::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')
            ->get('/admin/resource/supplier-resource/supplier-index-page');

        $response->assertOk();
    }

    public function test_staff_can_create_a_supplier(): void
    {
        $staff = MoonshineUser::factory()->create();

        $response = $this->actingAs($staff, 'moonshine')->post('/admin/resource/supplier-resource/crud', [
            'name' => 'AutoParts Direct',
            'slug' => 'autoparts-direct',
            'api_driver' => 'rest',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('suppliers', ['name' => 'AutoParts Direct', 'slug' => 'autoparts-direct', 'is_active' => true]);
    }
}
