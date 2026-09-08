<?php

namespace Tests\Unit;

use App\Services\Payments\PayseraSignature;
use App\Services\Payments\PayseraWebhookException;
use Tests\TestCase;

/**
 * Exercises the hand-rolled Paysera webhook signature verification
 * directly — see PayseraSignature's docblock for why this isn't an SDK.
 */
class PayseraSignatureTest extends TestCase
{
    private const SECRET = 'client_secret_test';

    public function test_a_correctly_signed_payload_verifies(): void
    {
        $payload = '{"event":{"type":"order"}}';

        PayseraSignature::verify($payload, $this->sign($payload), (string) time(), self::SECRET);

        $this->addToAssertionCount(1);
    }

    public function test_it_verifies_without_a_created_at_header(): void
    {
        $payload = '{"event":{"type":"order"}}';

        PayseraSignature::verify($payload, $this->sign($payload), null, self::SECRET);

        $this->addToAssertionCount(1);
    }

    public function test_a_tampered_payload_is_rejected(): void
    {
        $signature = $this->sign('{"event":{"type":"order"}}');

        $this->expectException(PayseraWebhookException::class);

        PayseraSignature::verify('{"event":{"type":"something-else"}}', $signature, null, self::SECRET);
    }

    public function test_the_wrong_secret_is_rejected(): void
    {
        $payload = '{"event":{"type":"order"}}';
        $signature = $this->sign($payload, secret: 'a_different_secret');

        $this->expectException(PayseraWebhookException::class);

        PayseraSignature::verify($payload, $signature, null, self::SECRET);
    }

    public function test_an_expired_created_at_is_rejected(): void
    {
        $payload = '{"event":{"type":"order"}}';

        $this->expectException(PayseraWebhookException::class);

        PayseraSignature::verify($payload, $this->sign($payload), (string) (time() - 600), self::SECRET);
    }

    public function test_a_missing_signature_is_rejected(): void
    {
        $this->expectException(PayseraWebhookException::class);

        PayseraSignature::verify('{}', null, null, self::SECRET);
    }

    private function sign(string $payload, ?string $secret = null): string
    {
        return hash_hmac('sha256', $payload, $secret ?? self::SECRET);
    }
}
