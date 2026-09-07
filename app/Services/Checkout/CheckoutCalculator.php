<?php

namespace App\Services\Checkout;

use App\Services\Cart\CartItem;
use Illuminate\Support\Collection;

/**
 * Computes checkout totals in integer minor currency units (cents) from
 * cart lines. This is the ONLY place shipping/tax rules are applied —
 * see config/checkout.php for why they're demo placeholders today, and
 * replace the two private methods below when real rules exist.
 */
class CheckoutCalculator
{
    /**
     * @param  Collection<int, CartItem>  $cartItems
     */
    public function calculate(Collection $cartItems): CheckoutTotals
    {
        $subtotalCents = $cartItems
            ->reject(fn (CartItem $item) => $item->needsAttention())
            ->sum('lineTotalCents');

        $shippingCents = $this->shippingCents($subtotalCents);
        $taxCents = $this->taxCents($subtotalCents);

        return new CheckoutTotals(
            subtotalCents: $subtotalCents,
            shippingCents: $shippingCents,
            taxCents: $taxCents,
            totalCents: $subtotalCents + $shippingCents + $taxCents,
        );
    }

    private function shippingCents(int $subtotalCents): int
    {
        if ($subtotalCents <= 0) {
            return 0;
        }

        if ($subtotalCents >= config('checkout.shipping.free_shipping_threshold_cents')) {
            return 0;
        }

        return config('checkout.shipping.flat_rate_cents');
    }

    private function taxCents(int $subtotalCents): int
    {
        return (int) round($subtotalCents * config('checkout.tax.rate_percent') / 100);
    }
}
