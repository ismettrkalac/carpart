<?php

namespace App\Enums;

/**
 * Which status an OrderStatusHistory entry recorded a change to — payment
 * and fulfillment are tracked as separate timelines (see PaymentStatus /
 * FulfillmentStatus).
 */
enum OrderStatusType: string
{
    case Payment = 'payment';
    case Fulfillment = 'fulfillment';
}
