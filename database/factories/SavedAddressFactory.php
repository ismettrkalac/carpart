<?php

namespace Database\Factories;

use App\Models\SavedAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedAddress>
 */
class SavedAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => $this->faker->randomElement(['Home', 'Office', null]),
            'name' => $this->faker->name(),
            'line1' => $this->faker->streetAddress(),
            'line2' => null,
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'postal_code' => $this->faker->postcode(),
            'country' => 'US',
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => ['is_default' => true]);
    }
}
