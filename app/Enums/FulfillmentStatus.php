<?php

namespace App\Enums;

/**
 * Fulfillment (shipping/handling) state of an order, tracked independently
 * of payment (see PaymentStatus). New orders start Unfulfilled regardless
 * of payment status — fulfillment and payment are separate concerns.
 */
enum FulfillmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function toString(): string
    {
        return match ($this) {
            self::Unfulfilled => 'Unfulfilled',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
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
            self::Unfulfilled => 'gray',
            self::Processing => 'info',
            self::Shipped => 'purple',
            self::Delivered => 'success',
            self::Cancelled => 'error',
        };
    }
}
