<?php

namespace Database\Factories;

use App\Models\Part;
use App\Models\PartImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartImage>
 */
class PartImageFactory extends Factory
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
            'path' => 'parts/'.$this->faker->uuid().'.jpg',
            'position' => 0,
        ];
    }
}
