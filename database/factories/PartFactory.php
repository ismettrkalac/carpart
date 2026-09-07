<?php

namespace Database\Factories;

use App\Enums\PartStatus;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Part>
 */
class PartFactory extends Factory
{
    /**
     * Plausible component names for each seeded category slug, so a part's
     * name actually matches the category it's filed under.
     *
     * @var array<string, array<int, string>>
     */
    private const CATEGORY_COMPONENTS = [
        'brakes' => ['Brake Pad Set', 'Brake Rotor', 'Brake Caliper', 'Brake Drum', 'Brake Shoe Set', 'Brake Line', 'Brake Master Cylinder'],
        'suspension' => ['Shock Absorber', 'Strut Assembly', 'Control Arm', 'Sway Bar Link', 'Coil Spring', 'Strut Mount', 'Ball Joint'],
        'engine-parts' => ['Timing Belt Kit', 'Head Gasket Set', 'Piston Ring Set', 'Oil Pump', 'Engine Mount', 'Valve Cover Gasket'],
        'filters' => ['Oil Filter', 'Air Filter', 'Cabin Air Filter', 'Fuel Filter', 'Transmission Filter'],
        'ignition' => ['Spark Plug', 'Ignition Coil', 'Ignition Switch', 'Distributor Cap', 'Ignition Wire Set'],
        'cooling-system' => ['Radiator', 'Thermostat', 'Radiator Hose', 'Cooling Fan', 'Water Pump', 'Coolant Reservoir'],
        'exhaust' => ['Muffler', 'Exhaust Pipe', 'Catalytic Converter', 'Exhaust Manifold', 'Oxygen Sensor'],
        'electrical' => ['Alternator', 'Starter Motor', 'Battery', 'Wiring Harness', 'Fuse Box'],
        'body-parts' => ['Headlight Assembly', 'Tail Light Assembly', 'Side Mirror', 'Door Handle', 'Bumper Cover', 'Fender'],
        'transmission' => ['Transmission Mount', 'Clutch Kit', 'Torque Converter', 'CV Axle Shaft', 'Shift Solenoid'],
    ];

    /**
     * Components that make sense with a position (front/rear/left/right).
     *
     * @var array<int, string>
     */
    private const POSITIONAL_COMPONENTS = [
        'Brake Pad Set', 'Brake Rotor', 'Brake Caliper', 'Brake Drum', 'Brake Shoe Set',
        'Shock Absorber', 'Strut Assembly', 'Control Arm', 'Sway Bar Link', 'Strut Mount', 'Ball Joint',
        'Headlight Assembly', 'Tail Light Assembly', 'Side Mirror', 'Door Handle', 'Fender', 'CV Axle Shaft',
    ];

    /**
     * @var array<int, string>
     */
    private const POSITIONS = ['Front', 'Rear', 'Left', 'Right', 'Front Left', 'Front Right', 'Rear Left', 'Rear Right'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->generatePartName();

        return [
            'manufacturer_id' => Manufacturer::factory(),
            'category_id' => Category::factory(),
            'sku' => strtoupper($this->faker->unique()->bothify('??-#####')),
            'name' => $name,
            'slug' => str($name)->slug().'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'description' => "Premium replacement {$name} engineered for a precise fit and dependable performance.",
            'status' => PartStatus::Active,
            'base_price_cents' => $this->faker->numberBetween(500, 50000),
            'currency' => 'USD',
            'stock_quantity' => $this->faker->numberBetween(0, 500),
            'weight_kg' => $this->faker->randomFloat(3, 0.05, 25),
        ];
    }

    /**
     * Generate a part whose name actually matches the given category, e.g.
     * a "brakes" category gets "Front Brake Rotor", not "Radiator".
     */
    public function forCategory(Category $category): static
    {
        return $this->state(function () use ($category): array {
            $name = $this->generatePartName($category->slug);

            return [
                'category_id' => $category->id,
                'name' => $name,
                'slug' => str($name)->slug().'-'.$this->faker->unique()->numberBetween(1000, 9999),
                'description' => "Premium replacement {$name} engineered for a precise fit and dependable performance.",
            ];
        });
    }

    private function generatePartName(?string $categorySlug = null): string
    {
        $components = self::CATEGORY_COMPONENTS[$categorySlug]
            ?? array_merge(...array_values(self::CATEGORY_COMPONENTS));

        $component = $this->faker->randomElement($components);

        if (in_array($component, self::POSITIONAL_COMPONENTS, true) && $this->faker->boolean(70)) {
            return $this->faker->randomElement(self::POSITIONS).' '.$component;
        }

        return $component;
    }
}
