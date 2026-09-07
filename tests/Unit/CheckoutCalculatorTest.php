<?php

namespace Tests\Unit;

use App\Enums\PartStatus;
use App\Models\Part;
use App\Services\Cart\CartItem;
use App\Services\Checkout\CheckoutCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_is_charged_below_the_free_threshold(): void
    {
        $part = Part::factory()->make(['manufacturer_id' => null, 'category_id' => null, 'status' => PartStatus::Active, 'stock_quantity' => 10, 'base_price_cents' => 5000]);
        $items = collect([CartItem::fromPart($part, 1)]);

        $totals = (new CheckoutCalculator)->calculate($items);

        $this->assertSame(5000, $totals->subtotalCents);
        $this->assertSame(config('checkout.shipping.flat_rate_cents'), $totals->shippingCents);
    }

    public function test_shipping_is_free_at_or_above_the_threshold(): void
    {
        $threshold = config('checkout.shipping.free_shipping_threshold_cents');
        $part = Part::factory()->make(['manufacturer_id' => null, 'category_id' => null, 'status' => PartStatus::Active, 'stock_quantity' => 10, 'base_price_cents' => $threshold]);
        $items = collect([CartItem::fromPart($part, 1)]);

        $totals = (new CheckoutCalculator)->calculate($items);

        $this->assertSame(0, $totals->shippingCents);
    }

    public function test_tax_is_a_percentage_of_subtotal_rounded_to_the_nearest_cent(): void
    {
        $part = Part::factory()->make(['manufacturer_id' => null, 'category_id' => null, 'status' => PartStatus::Active, 'stock_quantity' => 10, 'base_price_cents' => 1000]);
        $items = collect([CartItem::fromPart($part, 1)]);

        $totals = (new CheckoutCalculator)->calculate($items);

        $expectedTax = (int) round(1000 * config('checkout.tax.rate_percent') / 100);
        $this->assertSame($expectedTax, $totals->taxCents);
    }

    public function test_lines_needing_attention_are_excluded_from_the_subtotal(): void
    {
        $available = Part::factory()->make(['manufacturer_id' => null, 'category_id' => null, 'base_price_cents' => 5000, 'status' => PartStatus::Active, 'stock_quantity' => 10]);
        $items = collect([
            CartItem::fromPart($available, 1),
            CartItem::fromPart(null, 2), // deleted part
        ]);

        $totals = (new CheckoutCalculator)->calculate($items);

        $this->assertSame(5000, $totals->subtotalCents);
    }

    public function test_total_is_the_sum_of_subtotal_shipping_and_tax(): void
    {
        $part = Part::factory()->make(['manufacturer_id' => null, 'category_id' => null, 'status' => PartStatus::Active, 'stock_quantity' => 10, 'base_price_cents' => 5000]);
        $items = collect([CartItem::fromPart($part, 1)]);

        $totals = (new CheckoutCalculator)->calculate($items);

        $this->assertSame($totals->subtotalCents + $totals->shippingCents + $totals->taxCents, $totals->totalCents);
    }
}
