<?php

namespace App\Services\Cart;

use App\Enums\PartStatus;
use App\Models\Part;
use Illuminate\Support\Number;

/**
 * One cart line, resolved fresh against the database. The cart session
 * only ever stores {part_id => quantity} — everything else here (price,
 * availability, stock) is looked up live so it can never go stale.
 */
final readonly class CartItem
{
    public function __construct(
        public int $partId,
        public ?Part $part,
        public int $quantity,
        public int $unitPriceCents,
        public int $lineTotalCents,
        public bool $isAvailable,
        public int $stockQuantity,
    ) {}

    public static function fromPart(?Part $part, int $quantity): self
    {
        $isAvailable = $part !== null
            && $part->status === PartStatus::Active
            && $part->stock_quantity > 0;

        $unitPriceCents = $part?->base_price_cents ?? 0;

        return new self(
            partId: $part?->id ?? 0,
            part: $part,
            quantity: $quantity,
            unitPriceCents: $unitPriceCents,
            lineTotalCents: $unitPriceCents * $quantity,
            isAvailable: $isAvailable,
            stockQuantity: $part?->stock_quantity ?? 0,
        );
    }

    /**
     * True when the requested quantity is more than what's currently in
     * stock — doesn't block viewing the cart, but must block checkout.
     */
    public function exceedsStock(): bool
    {
        return $this->quantity > $this->stockQuantity;
    }

    public function needsAttention(): bool
    {
        return ! $this->isAvailable || $this->exceedsStock();
    }

    public function formattedLineTotal(): string
    {
        return Number::currency($this->lineTotalCents / 100, in: $this->part?->currency ?? 'USD');
    }
}
