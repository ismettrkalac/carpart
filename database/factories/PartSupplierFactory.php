<?php

namespace Database\Factories;

use App\Models\Part;
use App\Models\PartSupplier;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartSupplier>
 */
class PartSupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'part_id' => Part::factory(),
            'supplier_id' => Supplier::factory(),
            'supplier_sku' => strtoupper($this->faker->bothify('SUP-#####')),
            'cost_cents' => $this->faker->numberBetween(200, 30000),
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'last_synced_at' => now(),
        ];
    }
}
