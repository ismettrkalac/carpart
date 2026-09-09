<x-layout title="Your Orders">
    <div class="mx-auto max-w-4xl px-6 py-10">
        <a href="{{ route('account.profile') }}" class="text-sm text-neutral-500 hover:underline">&larr; Profile</a>
        <h1 class="mt-2 text-2xl font-semibold">Your Orders</h1>

        @if ($orders->isEmpty())
            <div class="mt-16 text-center text-neutral-500">
                <p class="font-medium">You haven't placed any orders yet.</p>
                <a href="{{ route('parts.index') }}" class="mt-6 inline-block rounded-md bg-neutral-900 px-5 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                    Shop Parts
                </a>
            </div>
        @else
            <div class="mt-8 space-y-4">
                @foreach ($orders as $order)
                    <a
                        href="{{ route('account.orders.show', $order) }}"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-neutral-200 p-4 hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700"
                    >
                        <div>
                            <p class="font-medium">Order #{{ $order->orderNumber() }}</p>
                            <p class="text-sm text-neutral-500">{{ $order->created_at->format('M j, Y') }} · {{ $order->items->count() }} item(s)</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium dark:bg-neutral-900">
                                {{ $order->fulfillment_status->toString() }}
                            </span>
                            <span class="font-semibold">{{ $order->formattedTotal() }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-layout>
