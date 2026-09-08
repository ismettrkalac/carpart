<?php

namespace App\Services\Payments;

/**
 * Verifies a Paysera webhook request's `X-Paysera-Signature` header per
 * https://developers.paysera.com/guides/checkout-modern/api-integration/webhooks:
 * HMAC-SHA256 of the raw request body, keyed with the OAuth client secret.
 * Reimplemented directly (Paysera's own docs show the same handful of
 * lines) rather than pulling in an SDK for one HMAC check — see
 * PayseraCheckoutService's docblock for why this project talks to
 * Paysera over plain HTTP.
 *
 * The X-Paysera-Created-At timestamp check below is a defense-in-depth
 * addition beyond what Paysera's own sample verifies — it isn't
 * documented as required, but costs nothing and narrows the replay
 * window if a signed payload were ever intercepted.
 */
class PayseraSignature
{
    private const TOLERANCE_SECONDS = 300;

    /**
     * @throws PayseraWebhookException
     */
    public static function verify(string $payload, ?string $signature, ?string $createdAt, string $secret): void
    {
        if ($secret === '') {
            throw new PayseraWebhookException('No webhook secret is configured.');
        }

        if ($signature === null || $signature === '') {
            throw new PayseraWebhookException('Missing X-Paysera-Signature header.');
        }

        if ($createdAt !== null && $createdAt !== '') {
            if (! ctype_digit($createdAt)) {
                throw new PayseraWebhookException('Malformed X-Paysera-Created-At header.');
            }

            if (abs(time() - (int) $createdAt) > self::TOLERANCE_SECONDS) {
                throw new PayseraWebhookException('Webhook timestamp is outside the allowed tolerance.');
            }
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expected, $signature)) {
            throw new PayseraWebhookException('Signature did not match the expected value.');
        }
    }
}
