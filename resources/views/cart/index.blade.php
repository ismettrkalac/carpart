<x-layout title="Your Cart">
    <div class="mx-auto max-w-5xl px-6 py-10">
        <h1 class="text-2xl font-semibold">Your Cart</h1>

        @if ($items->isEmpty())
            <div class="mt-16 text-center text-neutral-500">
                <p class="font-medium">Your cart is empty.</p>
                <p class="mt-1 text-sm">Browse the catalog to find parts for your business.</p>
                <a href="{{ route('parts.index') }}" class="mt-6 inline-block rounded-md bg-neutral-900 px-5 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                    Shop Parts
                </a>
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[1fr_320px]">
                <div class="space-y-4">
                    @foreach ($items as $item)
                        <div class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
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

                                <p class="whitespace-nowrap font-semibold">
                                    {{ $item->formattedLineTotal() }}
                                </p>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                @if ($item->part)
                                    <form
                                        method="POST"
                                        action="{{ route('cart.items.update', $item->part) }}"
                                        x-data="{ quantity: {{ $item->quantity }}, max: {{ max($item->stockQuantity, $item->quantity) }}, updating: false }"
                                        x-on:submit="updating = true"
                                        class="flex items-center gap-2"
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" x-on:click="quantity = Math.max(1, quantity - 1)" class="flex size-8 items-center justify-center rounded-md border border-neutral-300 dark:border-neutral-700" aria-label="Decrease quantity">−</button>
                                        <input type="number" name="quantity" x-model.number="quantity" min="1" :max="max" class="w-14 rounded-md border border-neutral-300 px-2 py-1 text-center text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                        <button type="button" x-on:click="quantity = Math.min(max, quantity + 1)" class="flex size-8 items-center justify-center rounded-md border border-neutral-300 dark:border-neutral-700" aria-label="Increase quantity">+</button>
                                        <button type="submit" :disabled="updating" class="rounded-md border border-neutral-300 px-3 py-1 text-sm disabled:opacity-60 dark:border-neutral-700">
                                            <span x-show="!updating">Update</span>
                                            <span x-show="updating" x-cloak>…</span>
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('cart.items.destroy', $item->partId) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-neutral-500 hover:text-red-600 hover:underline">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="h-fit rounded-lg border border-neutral-200 p-5 dark:border-neutral-800">
                    <h2 class="font-semibold">Order Summary</h2>
                    <div class="mt-4 flex justify-between text-sm">
                        <span class="text-neutral-500">Subtotal</span>
                        <span class="font-medium">{{ $subtotalFormatted }}</span>
                    </div>
                    <p class="mt-1 text-xs text-neutral-500">Shipping and tax are calculated at checkout.</p>

                    @if ($subtotalCents > 0)
                        <a href="{{ route('checkout.create') }}" class="mt-4 block rounded-md bg-neutral-900 py-2.5 text-center text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                            Proceed to Checkout
                        </a>
                    @else
                        <p class="mt-4 text-sm text-neutral-500">Resolve the items above to check out.</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layout>
