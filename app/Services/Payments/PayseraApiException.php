<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * Thrown when a call to Paysera's API itself fails (network error, failed
 * authentication, or Paysera rejects the request). Never thrown for a
 * successful response — see PayseraCheckoutService::handleWebhookEvent()
 * for how a payment outcome is represented instead.
 */
class PayseraApiException extends RuntimeException {}
