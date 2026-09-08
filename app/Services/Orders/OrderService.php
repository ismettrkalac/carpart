<?php

namespace App\Services\Orders;

use App\Enums\OrderActorType;
use App\Enums\OrderStatusType;
use App\Models\Order;
use App\Services\Cart\CartItem;
use App\Services\Checkout\Address;
use App\Services\Checkout\CheckoutTotals;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Creates orders and their line items. Every order is created
 * pending_payment/unfulfilled — this class has no knowledge of any
 * payment provider; that lives in App\Services\Payments\PayseraCheckoutService,
 * which uses this order's total_cents/currency/uuid to start a Paysera
 * Checkout session after create() returns.
 *
 * Order/OrderItem's snapshot fields (sku/name/unit_price_cents) are what
 * that payment/receipt step (and the stock decrement in
 * App\Services\Inventory\StockDeductionService, once paid) rely on.
 */
class OrderService
{
    /**
     * @param  Collection<int, CartItem>  $cartItems  must already be
     *                                                revalidated by the
     *                                                caller (no item
     *                                                needsAttention())
     */
    public function create(
        Collection $cartItems,
        CheckoutTotals $totals,
        string $email,
        Address $shipping,
        Address $billing,
        ?int $userId,
        string $idempotencyKey,
    ): Order {
        $existing = Order::where('idempotency_key', $idempotencyKey)->first();

        if ($existing !== null) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($cartItems, $totals, $email, $shipping, $billing, $userId, $idempotencyKey): Order {
                $order = Order::create([
                    'user_id' => $userId,
                    'email' => $email,
                    ...$shipping->toAttributes('shipping'),
                    ...$billing->toAttributes('billing'),
                    'subtotal_cents' => $totals->subtotalCents,
                    'shipping_cents' => $totals->shippingCents,
                    'tax_cents' => $totals->taxCents,
                    'total_cents' => $totals->totalCents,
                    'currency' => $totals->currency,
                    'idempotency_key' => $idempotencyKey,
                ]);

                // payment_status/fulfillment_status are deliberately not
                // mass-assignable (see Order::$fillable); their DB defaults
                // apply on insert, but Eloquent's in-memory model won't
                // reflect that until refreshed.
                $order->refresh();

                $order->statusHistories()->create([
                    'status_type' => OrderStatusType::Fulfillment,
                    'from_status' => null,
                    'to_status' => $order->fulfillment_status->value,
                    'actor_type' => $userId !== null ? OrderActorType::Customer : OrderActorType::System,
                    'actor_id' => $userId,
                    'note' => 'Order placed.',
                ]);

                foreach ($cartItems as $item) {
                    if ($item->part === null || $item->needsAttention()) {
                        // The caller (CheckoutController) is responsible for
                        // blocking submission when any line needs attention;
                        // this is just a last-resort safety net.
                        continue;
                    }

                    $order->items()->create([
                        'part_id' => $item->part->id,
                        'sku' => $item->part->sku,
                        'name' => $item->part->name,
                        'unit_price_cents' => $item->unitPriceCents,
                        'quantity' => $item->quantity,
                        'line_total_cents' => $item->lineTotalCents,
                    ]);
                }

                return $order;
            });
        } catch (UniqueConstraintViolationException) {
            // A concurrent duplicate request won the race and created the
            // order for this key first — return that one instead of failing.
            return Order::where('idempotency_key', $idempotencyKey)->firstOrFail();
        }
    }
}
