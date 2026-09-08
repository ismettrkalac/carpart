<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * Thrown when an inbound Paysera webhook request's signature can't be
 * verified — a missing/malformed header, a mismatched secret, or (if
 * X-Paysera-Created-At is present) a timestamp outside the replay-
 * protection tolerance. Never thrown for a signature that verifies but
 * carries an event type/payload we don't otherwise act on.
 */
class PayseraWebhookException extends RuntimeException {}
