<?php

namespace App\Services\Orders;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderActorType;
use App\Enums\OrderStatusType;
use App\Enums\PaymentStatus;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Services\Inventory\StockReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * The single place fulfillment-status rules live. Both the MoonShine staff
 * panel and any future customer/API surface must go through this class —
 * never set Order::fulfillment_status directly.
 *
 * Payments are entirely out of scope here: this service never sets
 * PaymentStatus. That only ever happens once a real payment provider is
 * integrated (see the class docblock on OrderService for where).
 */
class OrderFulfillmentService
{
    public function __construct(
        private readonly StockReservationService $stockReservation = new StockReservationService,
    ) {}

    /**
     * Valid fulfillment transitions, keyed by current status value.
     * Cancellation is not allowed once an order has shipped — at that
     * point it's a returns/refund process, out of scope here.
     *
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        'unfulfilled' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ];

    /**
     * Statuses that require payment to be confirmed first — blocked while
     * an order is still pending_payment.
     *
     * @var array<int, string>
     */
    private const REQUIRES_PAID = ['processing', 'shipped'];

    /**
     * @throws OrderTransitionException
     */
    public function transitionTo(
        Order $order,
        FulfillmentStatus $to,
        OrderActorType $actorType,
        ?int $actorId,
        ?string $note = null,
    ): Order {
        $transitioned = false;

        $locked = DB::transaction(function () use ($order, $to, $actorType, $actorId, $note, &$transitioned): Order {
            // Pessimistic lock: two staff clicking "Ship" on the same
            // order at once must not both succeed independently.
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            // Idempotent no-op: repeating the same action (double-click,
            // retried request) must not create a duplicate history entry
            // or fail with a "no such transition" error.
            if ($locked->fulfillment_status === $to) {
                return $locked;
            }

            $from = $locked->fulfillment_status;

            if (! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
                throw new OrderTransitionException(
                    "Cannot move an order from \"{$from->value}\" to \"{$to->value}\"."
                );
            }

            if (in_array($to->value, self::REQUIRES_PAID, true) && $locked->payment_status !== PaymentStatus::Paid) {
                throw new OrderTransitionException(
                    'This order is still pending payment and cannot be processed or shipped yet.'
                );
            }

            $locked->fulfillment_status = $to;
            $locked->save();

            $locked->statusHistories()->create([
                'status_type' => OrderStatusType::Fulfillment,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'note' => $note,
            ]);

            if ($to === FulfillmentStatus::Cancelled) {
                // Releases whatever's left of this order's stock
                // reservation — a no-op if payment already went through
                // and StockDeductionService released it first.
                $this->stockReservation->releaseForOrder($locked);
            }

            $transitioned = true;

            return $locked;
        });

        // Only a genuine unfulfilled/processing -> shipped transition sends
        // an email — not the idempotent no-op above, and not any other
        // transition.
        if ($transitioned && $to === FulfillmentStatus::Shipped) {
            Mail::to($locked->email)->queue(new OrderShippedMail($locked));
        }

        return $locked;
    }

    /**
     * @throws OrderTransitionException
     */
    public function cancel(Order $order, OrderActorType $actorType, ?int $actorId, ?string $note = null): Order
    {
        return $this->transitionTo($order, FulfillmentStatus::Cancelled, $actorType, $actorId, $note);
    }

    /**
     * @return array<int, FulfillmentStatus>
     */
    public function availableTransitions(Order $order): array
    {
        $allowed = self::TRANSITIONS[$order->fulfillment_status->value] ?? [];

        return array_values(array_filter(
            array_map(fn (string $value) => FulfillmentStatus::from($value), $allowed),
            fn (FulfillmentStatus $status) => ! in_array($status->value, self::REQUIRES_PAID, true)
                || $order->payment_status === PaymentStatus::Paid,
        ));
    }
}
