@props(['part'])

<div class="group flex flex-col rounded-lg border border-neutral-200 p-4 transition hover:border-neutral-300 hover:shadow-sm dark:border-neutral-800 dark:hover:border-neutral-700">
    <a href="{{ route('parts.show', $part) }}" class="flex flex-1 flex-col">
        <div class="flex items-start justify-between gap-2">
            <x-part-image :part="$part" image-class="size-8 rounded-md object-cover" icon-class="size-8 text-neutral-400" />
            @if ($part->isInStock())
                <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-400">In stock</span>
            @else
                <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium text-neutral-500 dark:bg-neutral-900">Out of stock</span>
            @endif
        </div>

        <h3 class="mt-3 font-medium text-neutral-900 group-hover:underline dark:text-white">
            {{ $part->name }}
        </h3>

        <p class="mt-1 text-sm text-neutral-500">
            {{ $part->manufacturer?->name ?? 'Unbranded' }} · SKU {{ $part->sku }}
        </p>

        <p class="mt-3 text-lg font-semibold">
            {{ $part->formattedPrice() }}
        </p>
    </a>

    @if ($part->isInStock())
        <form
            method="POST"
            action="{{ route('cart.items.store', $part) }}"
            class="mt-3"
            x-data="{ adding: false }"
            x-on:submit="adding = true"
        >
            @csrf
            <input type="hidden" name="quantity" value="1">
            <button
                type="submit"
                :disabled="adding"
                class="w-full rounded-md border border-neutral-300 py-1.5 text-sm font-medium disabled:opacity-60 dark:border-neutral-700"
            >
                <span x-show="!adding">Add to cart</span>
                <span x-show="adding" x-cloak>Adding…</span>
            </button>
        </form>
    @endif
</div>
