<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Shipping & Tax Rules
    |--------------------------------------------------------------------------
    |
    | The project has no real shipping-carrier or tax-jurisdiction rules
    | integrated yet, so these are clearly-labeled placeholder rules:
    | a single flat shipping rate (waived above a threshold) and a single
    | blended tax rate applied to the subtotal. Replace this file's logic
    | (in App\Services\Checkout\CheckoutCalculator) with real carrier-rate
    | and tax-jurisdiction lookups when those are available — nothing else
    | in the checkout flow needs to change, since totals are only ever
    | computed through that one class.
    |
    */

    'shipping' => [
        'flat_rate_cents' => (int) env('CHECKOUT_SHIPPING_FLAT_CENTS', 999),
        'free_shipping_threshold_cents' => (int) env('CHECKOUT_FREE_SHIPPING_THRESHOLD_CENTS', 10000),
    ],

    'tax' => [
        // A single blended percentage applied to the subtotal, e.g. 7.25 for 7.25%.
        'rate_percent' => (float) env('CHECKOUT_TAX_RATE_PERCENT', 7.25),
    ],

];
