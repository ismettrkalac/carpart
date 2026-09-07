<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPriceCents = $this->faker->numberBetween(500, 20000);
        $quantity = $this->faker->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'part_id' => Part::factory(),
            'sku' => strtoupper($this->faker->bothify('??-#####')),
            'name' => ucfirst($this->faker->words(3, true)),
            'unit_price_cents' => $unitPriceCents,
            'quantity' => $quantity,
            'line_total_cents' => $unitPriceCents * $quantity,
        ];
    }
}
