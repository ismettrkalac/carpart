@props(['part'])

<a
    href="{{ route('parts.show', $part) }}"
    class="group flex flex-col rounded-lg border border-neutral-200 p-4 transition hover:border-neutral-300 hover:shadow-sm dark:border-neutral-800 dark:hover:border-neutral-700"
>
    <div class="flex items-start justify-between gap-2">
        <x-category-icon :slug="$part->category?->slug" class="size-8 shrink-0 text-neutral-400" />
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
