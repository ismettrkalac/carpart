<?php

namespace App\Services\Checkout;

use Illuminate\Support\Number;

final readonly class CheckoutTotals
{
    public function __construct(
        public int $subtotalCents,
        public int $shippingCents,
        public int $taxCents,
        public int $totalCents,
        public string $currency = 'USD',
    ) {}

    public function formattedSubtotal(): string
    {
        return Number::currency($this->subtotalCents / 100, in: $this->currency);
    }

    public function formattedShipping(): string
    {
        return $this->shippingCents === 0
            ? 'Free'
            : Number::currency($this->shippingCents / 100, in: $this->currency);
    }

    public function formattedTax(): string
    {
        return Number::currency($this->taxCents / 100, in: $this->currency);
    }

    public function formattedTotal(): string
    {
        return Number::currency($this->totalCents / 100, in: $this->currency);
    }
}
