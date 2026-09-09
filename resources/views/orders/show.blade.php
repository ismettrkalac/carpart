<x-layout title="Order confirmation">
    <div class="mx-auto max-w-3xl px-6 py-10">
        @if ($order->payment_status === \App\Enums\PaymentStatus::Paid)
            <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 dark:bg-green-950 dark:text-green-300">
                <p class="font-medium">Payment received. Thank you!</p>
            </div>
        @elseif ($order->payment_status === \App\Enums\PaymentStatus::Refunded)
            <div class="rounded-md bg-neutral-100 p-4 text-sm text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                <p class="font-medium">This order was refunded.</p>
                <p class="mt-1">The full amount was returned to your original payment method — allow a few business days for it to appear.</p>
            </div>
        @elseif ($order->payment_status === \App\Enums\PaymentStatus::Failed)
            <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-300">
                <p class="font-medium">Payment failed.</p>
                <p class="mt-1">No charge was completed. You can try again below.</p>
                <form method="POST" action="{{ route('orders.pay', $order) }}" class="mt-3">
                    @csrf
                    <button type="submit" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                        Try payment again
                    </button>
                </form>
            </div>
        @elseif ($order->payment_provider !== null)
            <div class="rounded-md bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                <p class="font-medium">Waiting for payment confirmation.</p>
                <p class="mt-1">If you completed payment, this page will update shortly. If you closed the payment page without finishing, you can pick up where you left off.</p>
                <form method="POST" action="{{ route('orders.pay', $order) }}" class="mt-3">
                    @csrf
                    <button type="submit" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                        Complete payment
                    </button>
                </form>
            </div>
        @else
            <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 dark:bg-green-950 dark:text-green-300">
                <p class="font-medium">Order received. Payment has not been collected.</p>
                <p class="mt-1">
                    This version of the site doesn't process payments yet, so availability is not guaranteed until
                    payment is implemented and completed — we'll be in touch to confirm before fulfilling this order.
                </p>
            </div>
        @endif

        <div class="mt-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Order #{{ strtoupper(substr($order->uuid, 0, 8)) }}</h1>
                <p class="mt-1 text-sm text-neutral-500">Placed {{ $order->created_at->format('M j, Y \a\t g:i A') }} · {{ $order->email }}</p>
            </div>
            <div class="flex gap-2">
                <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium dark:bg-neutral-900">
                    Payment: {{ str($order->payment_status->value)->replace('_', ' ')->title() }}
                </span>
                <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium dark:bg-neutral-900">
                    Fulfillment: {{ str($order->fulfillment_status->value)->title() }}
                </span>
            </div>
        </div>

        <div class="mt-8 rounded-lg border border-neutral-200 dark:border-neutral-800">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-neutral-200 text-neutral-500 dark:border-neutral-800">
                    <tr>
                        <th class="px-4 py-3 font-medium">Item</th>
                        <th class="px-4 py-3 font-medium">SKU</th>
                        <th class="px-4 py-3 text-right font-medium">Qty</th>
                        <th class="px-4 py-3 text-right font-medium">Unit price</th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-900">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-4 py-3">{{ $item->name }}</td>
                            <td class="px-4 py-3 text-neutral-500">{{ $item->sku }}</td>
                            <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right">{{ \Illuminate\Support\Number::currency($item->unit_price_cents / 100, in: $order->currency) }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ $item->formattedLineTotal($order->currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="space-y-1.5 border-t border-neutral-200 p-4 text-sm dark:border-neutral-800">
                <div class="flex justify-between">
                    <span class="text-neutral-500">Subtotal</span>
                    <span>{{ $order->formattedSubtotal() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-neutral-500">Shipping</span>
                    <span>{{ $order->formattedShipping() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-neutral-500">Tax</span>
                    <span>{{ $order->formattedTax() }}</span>
                </div>
                <div class="flex justify-between border-t border-neutral-200 pt-1.5 text-base font-semibold dark:border-neutral-800">
                    <span>Total</span>
                    <span>{{ $order->formattedTotal() }}</span>
                </div>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <h2 class="font-semibold">Shipping address</h2>
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $order->shipping_name }}<br>
                    {{ $order->shipping_line1 }}<br>
                    @if ($order->shipping_line2)
                        {{ $order->shipping_line2 }}<br>
                    @endif
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                    {{ $order->shipping_country }}
                </p>
            </div>
            <div>
                <h2 class="font-semibold">Billing address</h2>
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $order->billing_name }}<br>
                    {{ $order->billing_line1 }}<br>
                    @if ($order->billing_line2)
                        {{ $order->billing_line2 }}<br>
                    @endif
                    {{ $order->billing_city }}, {{ $order->billing_state }} {{ $order->billing_postal_code }}<br>
                    {{ $order->billing_country }}
                </p>
            </div>
        </div>

        <a href="{{ route('parts.index') }}" class="mt-8 inline-block text-sm font-medium hover:underline">
            ← Continue shopping
        </a>
    </div>
</x-layout>
