<?php

namespace App\Services\Inventory;

use App\Models\Order;
use App\Models\StockReservation;

/**
 * Creates and releases the time-boxed holds StockChecker subtracts from
 * available stock. Reservations are created once, when an order is
 * placed (see App\Services\Orders\OrderService), and released either when
 * payment is confirmed — the units are then actually decremented, see
 * StockDeductionService — or when the order is cancelled before that
 * happens, see App\Services\Orders\OrderFulfillmentService.
 */
class StockReservationService
{
    public function reserveForOrder(Order $order): void
    {
        $order->loadMissing('items');
        $expiresAt = now()->addMinutes((int) config('checkout.reservation_minutes'));

        foreach ($order->items as $item) {
            if ($item->part_id === null) {
                // Nothing to reserve for a line whose Part has already
                // been deleted — the order's own item snapshot is
                // unaffected either way.
                continue;
            }

            StockReservation::create([
                'part_id' => $item->part_id,
                'order_id' => $order->id,
                'quantity' => $item->quantity,
                'expires_at' => $expiresAt,
            ]);
        }
    }

    public function releaseForOrder(Order $order): void
    {
        StockReservation::where('order_id', $order->id)->delete();
    }
}
