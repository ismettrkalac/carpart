<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Part;
use App\Models\PartFitment;
use App\Models\PriceTier;
use App\Models\Supplier;
use App\Models\User;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo catalog data.
     *
     * The generated parts, manufacturers, and fitments below are randomly
     * fabricated for local development only. Fitment year/make/model ranges
     * are NOT verified compatibility data and must never be presented to
     * real customers as such.
     */
    public function run(): void
    {
        PriceTier::factory()->create(['name' => 'Retail', 'slug' => 'retail', 'discount_percent' => 0, 'is_default' => true]);
        $wholesale = PriceTier::factory()->create(['name' => 'Wholesale', 'slug' => 'wholesale', 'discount_percent' => 15]);
        PriceTier::factory()->create(['name' => 'Distributor', 'slug' => 'distributor', 'discount_percent' => 30]);

        $manufacturers = Manufacturer::factory(5)->create();
        // Exactly the 10 named categories, with clean slugs (no random
        // suffix) so PartFactory::forCategory() can match parts to them by
        // slug (e.g. 'brakes').
        $categories = collect(CategoryFactory::NAMES)->map(
            fn (string $name) => Category::factory()->create(['name' => $name, 'slug' => str($name)->slug()])
        );
        $suppliers = Supplier::factory(3)->create();

        $categories->each(function (Category $category) use ($manufacturers, $suppliers): void {
            Part::factory(random_int(4, 7))
                ->forCategory($category)
                ->recycle($manufacturers)
                ->create()
                ->each(function (Part $part) use ($suppliers): void {
                    $part->suppliers()->attach(
                        $suppliers->random(random_int(1, 2))->pluck('id'),
                        ['supplier_sku' => strtoupper(fake()->bothify('SUP-#####')), 'cost_cents' => (int) ($part->base_price_cents * 0.6)]
                    );

                    PartFitment::factory(random_int(1, 3))->create(['part_id' => $part->id]);
                });
        });

        $business = Business::factory()->approved()->create([
            'name' => 'Acme Auto Repair',
            'price_tier_id' => $wholesale->id,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'business_id' => $business->id,
            'role' => UserRole::Owner,
        ]);
    }
}
