<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Part;
use App\Models\StockReservation;
use App\Services\Inventory\StockReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_reservation_for_each_order_item(): void
    {
        $partA = Part::factory()->create();
        $partB = Part::factory()->create();
        $order = Order::factory()->create();
        $this->addItem($order, $partA, 2);
        $this->addItem($order, $partB, 1);

        app(StockReservationService::class)->reserveForOrder($order);

        $this->assertSame(2, StockReservation::where('order_id', $order->id)->count());
        $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'part_id' => $partA->id, 'quantity' => 2]);
        $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'part_id' => $partB->id, 'quantity' => 1]);
    }

    public function test_the_reservation_expires_after_the_configured_window(): void
    {
        config(['checkout.reservation_minutes' => 45]);
        $this->travelTo(now());
        $part = Part::factory()->create();
        $order = Order::factory()->create();
        $this->addItem($order, $part, 1);

        app(StockReservationService::class)->reserveForOrder($order);

        $reservation = StockReservation::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(now()->addMinutes(45)->toDateTimeString(), $reservation->expires_at->toDateTimeString());
    }

    public function test_releasing_an_order_deletes_its_reservations(): void
    {
        $order = Order::factory()->create();
        StockReservation::factory()->for(Part::factory())->for($order)->create();
        StockReservation::factory()->for(Part::factory())->for($order)->create();

        app(StockReservationService::class)->releaseForOrder($order);

        $this->assertSame(0, StockReservation::where('order_id', $order->id)->count());
    }

    public function test_releasing_an_order_does_not_touch_other_orders_reservations(): void
    {
        $part = Part::factory()->create();
        $order = Order::factory()->create();
        $otherOrder = Order::factory()->create();
        StockReservation::factory()->for($part)->for($otherOrder)->create();

        app(StockReservationService::class)->releaseForOrder($order);

        $this->assertSame(1, StockReservation::where('order_id', $otherOrder->id)->count());
    }

    private function addItem(Order $order, Part $part, int $quantity): void
    {
        $order->items()->create([
            'part_id' => $part->id,
            'sku' => $part->sku,
            'name' => $part->name,
            'unit_price_cents' => $part->base_price_cents,
            'quantity' => $quantity,
            'line_total_cents' => $part->base_price_cents * $quantity,
        ]);
    }
}
