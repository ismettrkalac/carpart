<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(1000, 50000);
        $shipping = 999;
        $tax = (int) round($subtotal * 0.0725);

        return [
            'user_id' => null,
            'email' => $this->faker->safeEmail(),

            'shipping_name' => $this->faker->name(),
            'shipping_line1' => $this->faker->streetAddress(),
            'shipping_line2' => null,
            'shipping_city' => $this->faker->city(),
            'shipping_state' => $this->faker->stateAbbr(),
            'shipping_postal_code' => $this->faker->postcode(),
            'shipping_country' => 'US',

            'billing_name' => $this->faker->name(),
            'billing_line1' => $this->faker->streetAddress(),
            'billing_line2' => null,
            'billing_city' => $this->faker->city(),
            'billing_state' => $this->faker->stateAbbr(),
            'billing_postal_code' => $this->faker->postcode(),
            'billing_country' => 'US',

            'subtotal_cents' => $subtotal,
            'shipping_cents' => $shipping,
            'tax_cents' => $tax,
            'total_cents' => $subtotal + $shipping + $tax,
            'currency' => 'USD',

            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
