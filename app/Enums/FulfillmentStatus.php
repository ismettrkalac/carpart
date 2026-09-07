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
}
