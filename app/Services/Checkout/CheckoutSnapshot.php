<?php

namespace App\Services\Checkout;

use App\Services\Cart\CartItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

/**
 * What the shopper was shown when the checkout page last rendered:
 * per-line price/availability plus the total, keyed by a one-time token.
 * Stored server-side (session) and compared against a fresh recomputation
 * on submission — never trust anything about price or availability from
 * the request itself.
 */
final readonly class CheckoutSnapshot
{
    /**
     * @param  array<int, array{part_id: int, name: string, quantity: int, unit_price_cents: int, available: bool}>  $lines
     */
    public function __construct(
        public string $token,
        public array $lines,
        public int $totalCents,
    ) {}

    /**
     * @param  Collection<int, CartItem>  $cartItems
     */
    public static function capture(string $token, Collection $cartItems, CheckoutTotals $totals): self
    {
        return new self(
            token: $token,
            lines: $cartItems->map(fn (CartItem $item): array => [
                'part_id' => $item->partId,
                'name' => $item->part?->name ?? "Item #{$item->partId}",
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unitPriceCents,
                'available' => ! $item->needsAttention(),
            ])->all(),
            totalCents: $totals->totalCents,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toSessionArray(): array
    {
        return ['token' => $this->token, 'lines' => $this->lines, 'total_cents' => $this->totalCents];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromSessionArray(array $data): self
    {
        return new self($data['token'], $data['lines'], $data['total_cents']);
    }

    /**
     * Compare against a freshly-recomputed cart. An empty array means
     * nothing changed and it's safe to place the order for this snapshot.
     *
     * @param  Collection<int, CartItem>  $freshItems
     * @return array<int, string> human-readable descriptions of what changed
     */
    public function diff(Collection $freshItems, CheckoutTotals $freshTotals): array
    {
        $changes = [];
        $freshByPartId = $freshItems->keyBy('partId');

        foreach ($this->lines as $line) {
            /** @var CartItem|null $fresh */
            $fresh = $freshByPartId->get($line['part_id']);

            if ($fresh === null || $fresh->needsAttention()) {
                $changes[] = "\"{$line['name']}\" is no longer available in the quantity you selected.";

                continue;
            }

            if ($fresh->unitPriceCents !== $line['unit_price_cents']) {
                $changes[] = "\"{$line['name']}\" price changed from "
                    .Number::currency($line['unit_price_cents'] / 100)
                    .' to '
                    .Number::currency($fresh->unitPriceCents / 100).'.';
            }
        }

        if ($changes === [] && $freshTotals->totalCents !== $this->totalCents) {
            $changes[] = 'Your order total has changed.';
        }

        return $changes;
    }
}
