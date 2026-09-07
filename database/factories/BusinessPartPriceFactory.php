<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessPartPrice;
use App\Models\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessPartPrice>
 */
class BusinessPartPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'part_id' => Part::factory(),
            'price_cents' => $this->faker->numberBetween(500, 50000),
        ];
    }
}
