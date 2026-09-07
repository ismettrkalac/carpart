<?php

namespace Database\Factories;

use App\Models\Part;
use App\Models\PartFitment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartFitment>
 */
class PartFitmentFactory extends Factory
{
    /**
     * Real make/model pairs so demo fitment data reads plausibly. These are
     * NOT a claim of real-world fitment — see the disclaimer on the part
     * detail page.
     *
     * @var array<string, array<int, string>>
     */
    private const MODELS_BY_MAKE = [
        'Toyota' => ['Camry', 'Corolla', 'RAV4', 'Highlander', 'Tacoma'],
        'Ford' => ['F-150', 'Focus', 'Escape', 'Explorer', 'Fusion'],
        'Honda' => ['Civic', 'Accord', 'CR-V', 'Pilot', 'Odyssey'],
        'Chevrolet' => ['Silverado', 'Malibu', 'Equinox', 'Tahoe', 'Cruze'],
        'BMW' => ['3 Series', '5 Series', 'X3', 'X5'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $yearStart = $this->faker->numberBetween(2005, 2018);
        $make = $this->faker->randomElement(array_keys(self::MODELS_BY_MAKE));

        return [
            'part_id' => Part::factory(),
            'make' => $make,
            'model' => $this->faker->randomElement(self::MODELS_BY_MAKE[$make]),
            'year_start' => $yearStart,
            'year_end' => $yearStart + $this->faker->numberBetween(1, 6),
            'engine' => $this->faker->randomElement(['1.6L I4', '2.0L I4', '3.5L V6', '5.0L V8']),
            'trim' => $this->faker->randomElement(['Base', 'Sport', 'Limited', null]),
        ];
    }
}
