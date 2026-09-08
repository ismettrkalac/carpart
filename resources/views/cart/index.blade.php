<x-layout title="Your Cart">
    <div class="mx-auto max-w-5xl px-6 py-10">
        <h1 class="text-2xl font-semibold">Your Cart</h1>

        <div
            x-data="{ isEmpty: {{ $items->isEmpty() ? 'true' : 'false' }}, subtotalCents: {{ $subtotalCents }}, subtotalFormatted: {{ \Illuminate\Support\Js::from($subtotalFormatted) }} }"
            x-on:cart-line-updated="isEmpty = $event.detail.isEmpty; subtotalCents = $event.detail.subtotalCents; subtotalFormatted = $event.detail.subtotalFormatted"
        >
            <div x-show="isEmpty" x-cloak class="mt-16 text-center text-neutral-500">
                <p class="font-medium">Your cart is empty.</p>
                <p class="mt-1 text-sm">Browse the catalog to find parts for your business.</p>
                <a href="{{ route('parts.index') }}" class="mt-6 inline-block rounded-md bg-neutral-900 px-5 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                    Shop Parts
                </a>
            </div>

            <div x-show="!isEmpty" x-cloak class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[1fr_320px]">
                <div class="space-y-4">
                    @foreach ($items as $item)
                        <div
                            class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800"
                            @if ($item->part)
                                x-data="cartQuantity({
                                    quantity: {{ $item->quantity }},
                                    max: {{ max($item->stockQuantity, $item->quantity) }},
                                    updateUrl: {{ \Illuminate\Support\Js::from(route('cart.items.update', $item->part)) }},
                                    removeUrl: {{ \Illuminate\Support\Js::from(route('cart.items.destroy', $item->partId)) }},
                                })"
                            @endif
                        >
                            @if ($item->part)
                                @csrf
                            @endif

                            <div class="flex items-start gap-4">
                                <x-category-icon :slug="$item->part?->category?->slug" class="size-10 shrink-0 text-neutral-400" />

                                <div class="min-w-0 flex-1">
                                    @if ($item->part)
                                        <a href="{{ route('parts.show', $item->part) }}" class="font-medium hover:underline">
                                            {{ $item->part->name }}
                                        </a>
                                        <p class="text-sm text-neutral-500">SKU {{ $item->part->sku }}</p>
                                    @else
                                        <p class="font-medium text-neutral-500">Item no longer available</p>
                                    @endif

                                    @if ($item->needsAttention())
                                        <p class="mt-1 text-sm text-amber-600 dark:text-amber-500">
                                            @if (! $item->isAvailable)
                                                This product is no longer available. Please remove it to continue.
                                            @else
                                                Only {{ $item->stockQuantity }} available — please update the quantity.
                                            @endif
                                        </p>
                                    @endif
                                </div>

                                <p
                                    class="whitespace-nowrap font-semibold transition-colors duration-500"
                                    @if ($item->part)
                                        :class="{ 'animate-pulse text-neutral-400 dark:text-neutral-600': updating, 'text-green-600 dark:text-green-400': justUpdated }"
                                    @endif
                                    data-line-total
                                >
                                    {{ $item->formattedLineTotal() }}
                                </p>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                @if ($item->part)
                                    <div class="flex items-center gap-2">
                                        <button type="button" x-on:click="decrement()" :disabled="updating" class="flex size-8 items-center justify-center rounded-md border border-neutral-300 disabled:opacity-60 dark:border-neutral-700" aria-label="Decrease quantity">−</button>
                                        <input type="number" x-model.number="quantity" x-on:input="recalculate()" :disabled="updating" min="1" :max="max" class="w-14 rounded-md border border-neutral-300 px-2 py-1 text-center text-sm disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-900">
                                        <button type="button" x-on:click="increment()" :disabled="updating" class="flex size-8 items-center justify-center rounded-md border border-neutral-300 disabled:opacity-60 dark:border-neutral-700" aria-label="Increase quantity">+</button>
                                        <span x-show="updating" x-cloak class="flex items-center gap-1 text-sm text-neutral-500">
                                            <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                            Updating…
                                        </span>
                                    </div>

                                    <button type="button" x-on:click="remove()" :disabled="updating" class="text-sm text-neutral-500 hover:text-red-600 hover:underline disabled:opacity-60">
                                        Remove
                                    </button>
                                @else
                                    <form method="POST" action="{{ route('cart.items.destroy', $item->partId) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-neutral-500 hover:text-red-600 hover:underline">
                                            Remove
                                        </button>
                                    </form>
                                @endif
                            </div>

                            @if ($item->part)
                                <p x-show="error" x-cloak x-text="error" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="h-fit rounded-lg border border-neutral-200 p-5 dark:border-neutral-800">
                    <h2 class="font-semibold">Order Summary</h2>
                    <div class="mt-4 flex justify-between text-sm">
                        <span class="text-neutral-500">Subtotal</span>
                        <span class="font-medium" x-text="subtotalFormatted">{{ $subtotalFormatted }}</span>
                    </div>
                    <p class="mt-1 text-xs text-neutral-500">Shipping and tax are calculated at checkout.</p>

                    <a x-show="subtotalCents > 0" x-cloak href="{{ route('checkout.create') }}" class="mt-4 block rounded-md bg-neutral-900 py-2.5 text-center text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                        Proceed to Checkout
                    </a>
                    <p x-show="subtotalCents <= 0" x-cloak class="mt-4 text-sm text-neutral-500">Resolve the items above to check out.</p>
                </div>
            </div>
        </div>
    </div>
</x-layout>
