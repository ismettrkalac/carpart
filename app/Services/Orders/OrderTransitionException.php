<?php

namespace App\Services\Orders;

use RuntimeException;

/**
 * Thrown for a rejected fulfillment-status change (invalid transition, or
 * blocked because payment is still pending). The message is safe to show
 * directly to staff.
 */
class OrderTransitionException extends RuntimeException {}
