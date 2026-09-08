<?php

namespace App\Services\Inventory;

use App\Enums\PartStatus;
use App\Models\Part;

/**
 * Read-only stock checks used by the cart and checkout.
 *
 * Deliberately does nothing else here: it never reserves or decrements
 * Part::stock_quantity. A pending_payment order still has NO effect on
 * stock — availability is only ever a live read of the current quantity,
 * so two shoppers can both "check out" the last unit and both land on an
 * unpaid order. The actual decrement, once an order is confirmed paid,
 * lives in App\Services\Inventory\StockDeductionService instead — kept
 * separate so this class's read-only contract stays simple, and so
 * CartService/CheckoutCalculator never need to change.
 *
 * Not yet implemented: a time-boxed stock *reservation* while a shopper
 * is on the payment provider's page (e.g. a `Part.reserved_quantity`
 * column or a `stock_reservations` table), which would close the small
 * window between checkout-session creation and payment confirmation
 * where the last unit could still be oversold.
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
