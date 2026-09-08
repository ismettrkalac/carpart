<?php

namespace App\Enums;

/**
 * Payment state of an order, tracked independently of fulfillment
 * (see FulfillmentStatus). Only PendingPayment is ever set by the current
 * checkout flow — no payment provider is integrated yet. The remaining
 * cases exist so the schema doesn't need to change when one is added; see
 * app/Services/Orders/OrderService.php for where a payment webhook would
 * transition an order out of PendingPayment.
 */
enum PaymentStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function toString(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending Payment',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * A color name understood by the MoonShine admin UI's badge
     * component. Returned as a plain string (rather than importing
     * MoonShine's Color enum) so this domain enum has no dependency on
     * the admin package.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::Paid => 'success',
            self::Failed => 'error',
            self::Refunded => 'gray',
        };
    }
}
