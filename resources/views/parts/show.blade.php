<x-layout :title="$part->name">
    <div class="mx-auto max-w-7xl px-6 py-10">
        <nav class="text-sm text-neutral-500">
            <a href="{{ route('home') }}" class="hover:underline">Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('parts.index') }}" class="hover:underline">Shop Parts</a>
            @if ($part->category)
                <span class="mx-1">/</span>
                <a href="{{ route('categories.show', $part->category) }}" class="hover:underline">{{ $part->category->name }}</a>
            @endif
            <span class="mx-1">/</span>
            <span class="text-neutral-900 dark:text-white">{{ $part->name }}</span>
        </nav>

        <div class="mt-6 grid grid-cols-1 gap-10 lg:grid-cols-2">
            <div class="flex aspect-square items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900">
                <x-category-icon :slug="$part->category?->slug" class="size-24 text-neutral-300 dark:text-neutral-700" />
            </div>

            <div>
                <p class="text-sm text-neutral-500">{{ $part->manufacturer?->name ?? 'Unbranded' }} · SKU {{ $part->sku }}</p>
                <h1 class="mt-1 text-2xl font-semibold">{{ $part->name }}</h1>

                <p class="mt-4 text-3xl font-semibold">{{ $part->formattedPrice() }}</p>
                <p class="mt-1 text-sm text-neutral-500">List price. Sign in with an approved business account for wholesale pricing.</p>

                <div class="mt-4">
                    @if ($part->isInStock())
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-700 dark:bg-green-950 dark:text-green-400">
                            <span class="size-1.5 rounded-full bg-green-500"></span>
                            In stock ({{ $part->stock_quantity }} available)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-neutral-100 px-3 py-1 text-sm font-medium text-neutral-500 dark:bg-neutral-900">
                            <span class="size-1.5 rounded-full bg-neutral-400"></span>
                            Out of stock
                        </span>
                    @endif
                </div>

                @if ($part->isInStock())
                    <form
                        method="POST"
                        action="{{ route('cart.items.store', $part) }}"
                        class="mt-6"
                        x-data="{ quantity: {{ (int) old('quantity', 1) }}, max: {{ $part->stock_quantity }}, adding: false }"
                        x-on:submit="adding = true"
                    >
                        @csrf
                        <label for="quantity" class="block text-sm font-medium">Quantity</label>
                        <div class="mt-1 flex items-center gap-2">
                            <button
                                type="button"
                                x-on:click="quantity = Math.max(1, quantity - 1)"
                                class="flex size-9 items-center justify-center rounded-md border border-neutral-300 text-lg dark:border-neutral-700"
                                aria-label="Decrease quantity"
                            >−</button>
                            <input
                                type="number"
                                id="quantity"
                                name="quantity"
                                x-model.number="quantity"
                                min="1"
                                :max="max"
                                class="w-16 rounded-md border border-neutral-300 px-2 py-1.5 text-center dark:border-neutral-700 dark:bg-neutral-900"
                            >
                            <button
                                type="button"
                                x-on:click="quantity = Math.min(max, quantity + 1)"
                                class="flex size-9 items-center justify-center rounded-md border border-neutral-300 text-lg dark:border-neutral-700"
                                aria-label="Increase quantity"
                            >+</button>
                        </div>

                        <button
                            type="submit"
                            :disabled="adding"
                            class="mt-4 w-full rounded-md bg-neutral-900 py-2.5 text-sm font-medium text-white disabled:opacity-60 dark:bg-white dark:text-neutral-900 sm:w-auto sm:px-8"
                        >
                            <span x-show="!adding">Add to cart</span>
                            <span x-show="adding" x-cloak>Adding…</span>
                        </button>
                    </form>
                @endif

                <p class="mt-6 leading-relaxed text-neutral-600 dark:text-neutral-400">
                    {{ $part->description }}
                </p>

                <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-neutral-200 pt-6 text-sm dark:border-neutral-800">
                    <div>
                        <dt class="text-neutral-500">Category</dt>
                        <dd class="mt-0.5">{{ $part->category?->name ?? 'Uncategorized' }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-500">Manufacturer</dt>
                        <dd class="mt-0.5">{{ $part->manufacturer?->name ?? 'Unbranded' }}</dd>
                    </div>
                    @if ($part->weight_kg)
                        <div>
                            <dt class="text-neutral-500">Weight</dt>
                            <dd class="mt-0.5">{{ $part->weight_kg }} kg</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        @if ($part->fitments->isNotEmpty())
            <div class="mt-12 border-t border-neutral-200 pt-8 dark:border-neutral-800">
                <h2 class="text-lg font-semibold">Vehicle fitment</h2>
                <p class="mt-1 text-sm text-amber-600 dark:text-amber-500">
                    Demo data for development purposes only — not verified part compatibility. Confirm fitment
                    with the manufacturer or your parts specialist before ordering.
                </p>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[480px] text-left text-sm">
                        <thead class="text-neutral-500">
                            <tr>
                                <th class="pb-2 font-medium">Make</th>
                                <th class="pb-2 font-medium">Model</th>
                                <th class="pb-2 font-medium">Years</th>
                                <th class="pb-2 font-medium">Engine</th>
                                <th class="pb-2 font-medium">Trim</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-900">
                            @foreach ($part->fitments as $fitment)
                                <tr>
                                    <td class="py-2">{{ $fitment->make }}</td>
                                    <td class="py-2">{{ $fitment->model }}</td>
                                    <td class="py-2">{{ $fitment->year_start }}–{{ $fitment->year_end }}</td>
                                    <td class="py-2">{{ $fitment->engine ?? '—' }}</td>
                                    <td class="py-2">{{ $fitment->trim ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($related->isNotEmpty())
            <div class="mt-12 border-t border-neutral-200 pt-8 dark:border-neutral-800">
                <h2 class="text-lg font-semibold">More in {{ $part->category?->name }}</h2>
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $relatedPart)
                        <x-part-card :part="$relatedPart" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layout>
