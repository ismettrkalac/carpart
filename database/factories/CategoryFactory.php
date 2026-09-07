<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The 10 categories PartFactory::forCategory() knows how to generate
     * matching part names for. Seed with exactly these (see DatabaseSeeder)
     * to get a coherent catalog; elsewhere (tests, ad-hoc factories) a
     * random pick is fine since slugs get a unique suffix regardless.
     *
     * @var array<int, string>
     */
    public const NAMES = [
        'Brakes', 'Suspension', 'Engine Parts', 'Filters', 'Ignition',
        'Cooling System', 'Exhaust', 'Electrical', 'Body Parts', 'Transmission',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement(self::NAMES);

        return [
            'parent_id' => null,
            'name' => $name,
            'slug' => str($name)->slug().'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'position' => 0,
        ];
    }
}
