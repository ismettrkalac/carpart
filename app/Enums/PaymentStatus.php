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
}
