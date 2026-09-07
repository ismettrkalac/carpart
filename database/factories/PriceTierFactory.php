<?php

namespace Database\Factories;

use App\Models\PriceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceTier>
 */
class PriceTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(['Retail', 'Wholesale', 'Distributor', 'Fleet']);

        return [
            'name' => $name,
            'slug' => str($name)->slug(),
            'discount_percent' => $this->faker->numberBetween(0, 30),
            'is_default' => false,
        ];
    }
}
