<?php

namespace Tests\Unit;

use App\Enums\PartStatus;
use App\Models\Order;
use App\Models\Part;
use App\Models\StockReservation;
use App\Services\Inventory\StockChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_part_is_available_when_stock_covers_the_quantity(): void
    {
        $part = Part::factory()->create(['status' => PartStatus::Active, 'stock_quantity' => 5]);

        $this->assertTrue(app(StockChecker::class)->isAvailable($part, 5));
        $this->assertFalse(app(StockChecker::class)->isAvailable($part, 6));
    }

    public function test_a_draft_part_is_never_available(): void
    {
        $part = Part::factory()->create(['status' => PartStatus::Draft, 'stock_quantity' => 5]);

        $this->assertFalse(app(StockChecker::class)->isAvailable($part, 1));
    }

    public function test_an_active_reservation_reduces_available_quantity(): void
    {
        $part = Part::factory()->create(['status' => PartStatus::Active, 'stock_quantity' => 5]);
        StockReservation::factory()->for($part)->for(Order::factory())->create(['quantity' => 3]);

        $checker = app(StockChecker::class);

        $this->assertSame(2, $checker->availableQuantity($part));
        $this->assertTrue($checker->isAvailable($part, 2));
        $this->assertFalse($checker->isAvailable($part, 3));
    }

    public function test_an_expired_reservation_no_longer_reduces_availability(): void
    {
        $part = Part::factory()->create(['status' => PartStatus::Active, 'stock_quantity' => 5]);
        StockReservation::factory()->expired()->for($part)->for(Order::factory())->create(['quantity' => 5]);

        $this->assertSame(5, app(StockChecker::class)->availableQuantity($part));
    }

    public function test_available_quantity_never_goes_negative(): void
    {
        $part = Part::factory()->create(['status' => PartStatus::Active, 'stock_quantity' => 2]);
        // More reserved than on hand shouldn't normally happen, but must
        // still clamp rather than return a negative number.
        StockReservation::factory()->for($part)->for(Order::factory())->create(['quantity' => 5]);

        $this->assertSame(0, app(StockChecker::class)->availableQuantity($part));
    }

    public function test_reserved_quantities_are_batched_per_part(): void
    {
        $partA = Part::factory()->create(['stock_quantity' => 10]);
        $partB = Part::factory()->create(['stock_quantity' => 10]);
        StockReservation::factory()->for($partA)->for(Order::factory())->create(['quantity' => 2]);
        StockReservation::factory()->for($partA)->for(Order::factory())->create(['quantity' => 3]);
        StockReservation::factory()->for($partB)->for(Order::factory())->create(['quantity' => 1]);

        $reserved = app(StockChecker::class)->reservedQuantities([$partA->id, $partB->id]);

        $this->assertSame(5, $reserved[$partA->id]);
        $this->assertSame(1, $reserved[$partB->id]);
    }
}
