<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Part;
use App\Services\Inventory\StockDeductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockDeductionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_decrements_stock_for_each_item_on_the_order(): void
    {
        $partA = Part::factory()->create(['stock_quantity' => 10]);
        $partB = Part::factory()->create(['stock_quantity' => 5]);
        $order = Order::factory()->create();
        $this->addItem($order, $partA, 3);
        $this->addItem($order, $partB, 2);

        app(StockDeductionService::class)->deductForOrder($order);

        $this->assertSame(7, $partA->fresh()->stock_quantity);
        $this->assertSame(3, $partB->fresh()->stock_quantity);
    }

    public function test_it_never_decrements_stock_below_zero(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 2]);
        $order = Order::factory()->create();
        // Simulates stock having been reduced elsewhere after the order
        // was placed but before payment cleared.
        $this->addItem($order, $part, 5);

        app(StockDeductionService::class)->deductForOrder($order);

        $this->assertSame(0, $part->fresh()->stock_quantity);
    }

    public function test_a_deleted_part_is_skipped_without_error(): void
    {
        $part = Part::factory()->create(['stock_quantity' => 10]);
        $order = Order::factory()->create();
        $this->addItem($order, $part, 3);
        $part->delete();

        app(StockDeductionService::class)->deductForOrder($order);

        $this->assertTrue(true);
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
