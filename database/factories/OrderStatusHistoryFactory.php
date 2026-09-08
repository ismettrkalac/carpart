<?php

namespace Database\Factories;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderActorType;
use App\Enums\OrderStatusType;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status_type' => OrderStatusType::Fulfillment,
            'from_status' => FulfillmentStatus::Unfulfilled->value,
            'to_status' => FulfillmentStatus::Processing->value,
            'actor_type' => OrderActorType::Staff,
            'actor_id' => null,
            'note' => null,
        ];
    }
}
