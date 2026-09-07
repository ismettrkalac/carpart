<?php

namespace App\Services\Inventory;

use App\Enums\PartStatus;
use App\Models\Part;

/**
 * Read-only stock checks used by the cart and checkout.
 *
 * Deliberately does nothing else right now: it never reserves or
 * decrements Part::stock_quantity. A pending_payment order has NO effect
 * on stock — availability is only ever a live read of the current
 * quantity, so two shoppers can both "check out" the last unit and both
 * land on an unpaid order.
 *
 * This is where real inventory reservation belongs once payments exist:
 * - On payment-provider checkout-session creation, place a time-boxed
 *   reservation (e.g. decrement an `Part.reserved_quantity` column, or a
 *   separate `stock_reservations` table keyed by order_id) so the item
 *   can't be oversold while the shopper is on the payment page.
 * - On a verified, idempotent "payment succeeded" webhook, convert the
 *   reservation into a real decrement of `stock_quantity` and mark the
 *   order paid.
 * - On payment failure/expiry, release the reservation.
 * Keeping this class separate now means that logic can be added here
 * without CartService or CheckoutCalculator needing to change.
 */
class StockChecker
{
    public function isAvailable(Part $part, int $quantity): bool
    {
        return $part->status === PartStatus::Active
            && $quantity >= 1
            && $part->stock_quantity >= $quantity;
    }
}
