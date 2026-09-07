<?php

namespace Database\Factories;

use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\PriceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price_tier_id' => PriceTier::factory(),
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company().' LLC',
            'tax_id' => $this->faker->numerify('##-#######'),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'status' => BusinessStatus::Pending,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BusinessStatus::Approved,
            'approved_at' => now(),
        ]);
    }
}
