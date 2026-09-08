@php
    $steps = [
        \App\Enums\FulfillmentStatus::Unfulfilled,
        \App\Enums\FulfillmentStatus::Processing,
        \App\Enums\FulfillmentStatus::Shipped,
        \App\Enums\FulfillmentStatus::Delivered,
    ];
    $currentStepIndex = array_search($order->fulfillment_status, $steps, true);
@endphp

<x-layout title="Order #{{ $order->orderNumber() }}">
    <div class="mx-auto max-w-3xl px-6 py-10">
        <a href="{{ route('account.orders.index') }}" class="text-sm text-neutral-500 hover:underline">← Your orders</a>

        <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Order #{{ $order->orderNumber() }}</h1>
                <p class="mt-1 text-sm text-neutral-500">Placed {{ $order->created_at->format('M j, Y \a\t g:i A') }}</p>
            </div>
            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium dark:bg-neutral-900">
                Payment: {{ $order->payment_status->toString() }}
            </span>
        </div>

        @if ($order->payment_status->value === 'pending_payment')
            <div class="mt-6 rounded-md bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                Payment has not been collected for this order yet, so availability is not guaranteed until
                payment is completed.
            </div>
        @endif

        <div class="mt-8">
            @if ($order->fulfillment_status === \App\Enums\FulfillmentStatus::Cancelled)
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-300">
                    This order was cancelled.
                </div>
            @else
                <ol class="flex items-center">
                    @foreach ($steps as $index => $step)
                        <li class="flex flex-1 items-center {{ $index < count($steps) - 1 ? 'after:mx-2 after:h-px after:flex-1 after:content-[\'\']' : '' }} {{ $index <= $currentStepIndex ? 'after:bg-neutral-900 dark:after:bg-white' : 'after:bg-neutral-200 dark:after:bg-neutral-800' }}">
                            <div class="flex flex-col items-center gap-1 text-center">
                                <span class="flex size-7 items-center justify-center rounded-full text-xs font-semibold {{ $index <= $currentStepIndex ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : 'bg-neutral-200 text-neutral-500 dark:bg-neutral-800' }}">
                                    {{ $index + 1 }}
                                </span>
                                <span class="text-xs {{ $index <= $currentStepIndex ? 'font-medium' : 'text-neutral-500' }}">
                                    {{ $step->toString() }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

        @if ($order->carrier || $order->tracking_number || $order->tracking_url)
            <div class="mt-8 rounded-lg border border-neutral-200 p-4 text-sm dark:border-neutral-800">
                <h2 class="font-semibold">Shipment</h2>
                <p class="mt-2 text-neutral-600 dark:text-neutral-400">
                    @if ($order->carrier) Carrier: {{ $order->carrier }}<br> @endif
                    @if ($order->tracking_number) Tracking number: {{ $order->tracking_number }} @endif
                </p>
                @if ($order->tracking_url)
                    <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block text-sm font-medium underline">
                        Track package
                    </a>
                @endif
                <p class="mt-2 text-xs text-neutral-500">Tracking is updated manually by our team — this is not a live carrier feed.</p>
            </div>
        @endif

        <div class="mt-8 rounded-lg border border-neutral-200 dark:border-neutral-800">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-neutral-200 text-neutral-500 dark:border-neutral-800">
                    <tr>
                        <th class="px-4 py-3 font-medium">Item</th>
                        <th class="px-4 py-3 text-right font-medium">Qty</th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-900">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-4 py-3">{{ $item->name }}</td>
                            <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ $item->formattedLineTotal($order->currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="space-y-1.5 border-t border-neutral-200 p-4 text-sm dark:border-neutral-800">
                <div class="flex justify-between"><span class="text-neutral-500">Subtotal</span><span>{{ $order->formattedSubtotal() }}</span></div>
                <div class="flex justify-between"><span class="text-neutral-500">Shipping</span><span>{{ $order->formattedShipping() }}</span></div>
                <div class="flex justify-between"><span class="text-neutral-500">Tax</span><span>{{ $order->formattedTax() }}</span></div>
                <div class="flex justify-between border-t border-neutral-200 pt-1.5 text-base font-semibold dark:border-neutral-800">
                    <span>Total</span><span>{{ $order->formattedTotal() }}</span>
                </div>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <h2 class="font-semibold">Shipping address</h2>
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $order->shipping_name }}<br>
                    {{ $order->shipping_line1 }}<br>
                    @if ($order->shipping_line2){{ $order->shipping_line2 }}<br>@endif
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                    {{ $order->shipping_country }}
                </p>
            </div>
            <div>
                <h2 class="font-semibold">Billing address</h2>
                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $order->billing_name }}<br>
                    {{ $order->billing_line1 }}<br>
                    @if ($order->billing_line2){{ $order->billing_line2 }}<br>@endif
                    {{ $order->billing_city }}, {{ $order->billing_state }} {{ $order->billing_postal_code }}<br>
                    {{ $order->billing_country }}
                </p>
            </div>
        </div>
    </div>
</x-layout>
