<?php

namespace App\Services\Inventory;

use App\Models\Order;
use App\Models\Part;

/**
 * Decrements Part::stock_quantity for a newly paid order's items. Called
 * exactly once per order, from inside the same locked transaction that
 * transitions Order::payment_status to Paid — see
 * App\Services\Payments\PayseraCheckoutService::handleWebhookEvent(),
 * which already guards against a duplicate webhook delivery re-running
 * this. Never called standalone, so it carries no idempotency guard of
 * its own.
 *
 * Deliberately separate from StockChecker, which stays read-only and is
 * used by the cart/checkout to validate availability before any payment
 * exists — an unpaid order still never reserves or deducts stock.
 */
class StockDeductionService
{
    public function deductForOrder(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $part = Part::whereKey($item->part_id)->lockForUpdate()->first();

            if ($part === null) {
                // The part was deleted after the order was placed — the
                // order's item snapshot is unaffected either way, and
                // there's nothing left to decrement.
                continue;
            }

            $part->decrement('stock_quantity', min($item->quantity, $part->stock_quantity));
        }
    }
}
