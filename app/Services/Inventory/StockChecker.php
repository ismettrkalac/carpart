<?php

namespace App\Services\Inventory;

use App\Enums\PartStatus;
use App\Models\Part;
use App\Models\StockReservation;

/**
 * Read-only stock checks used by the cart and checkout. Never decrements
 * Part::stock_quantity itself — that only happens once an order is
 * confirmed paid, in StockDeductionService.
 *
 * "Available" here means stock_quantity minus any active (non-expired)
 * StockReservation rows for that part — see StockReservationService for
 * where those are created (when an order is placed) and released (once
 * paid, or if the order is cancelled first). That's what closes the
 * window between checkout-session creation and payment confirmation:
 * once one shopper's order reserves the last unit, a second shopper's
 * cart/checkout sees it as unavailable until that reservation is
 * released or expires.
 */
class StockChecker
{
    public function isAvailable(Part $part, int $quantity): bool
    {
        return $part->status === PartStatus::Active
            && $quantity >= 1
            && $this->availableQuantity($part) >= $quantity;
    }

    public function availableQuantity(Part $part): int
    {
        return max(0, $part->stock_quantity - ($this->reservedQuantities([$part->id])[$part->id] ?? 0));
    }

    /**
     * Reserved quantity per part, for a batch of parts at once — used by
     * the cart listing so it doesn't run one query per line.
     *
     * @param  iterable<int>  $partIds
     * @return array<int, int> part_id => reserved quantity
     */
    public function reservedQuantities(iterable $partIds): array
    {
        return StockReservation::query()
            ->whereIn('part_id', $partIds)
            ->active()
            ->selectRaw('part_id, sum(quantity) as reserved')
            ->groupBy('part_id')
            ->pluck('reserved', 'part_id')
            ->map(fn (mixed $reserved): int => (int) $reserved)
            ->all();
    }
}
