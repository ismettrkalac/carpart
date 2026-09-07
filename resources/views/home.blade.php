<x-layout>
    <section class="border-b border-neutral-200 dark:border-neutral-800">
        <div class="mx-auto max-w-7xl px-6 py-16 sm:py-24">
            <div class="max-w-2xl">
                <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                    Auto parts, sourced and priced for your business.
                </h1>
                <p class="mt-4 text-lg text-neutral-600 dark:text-neutral-400">
                    Browse our catalog of parts across brakes, suspension, engine, and more. Sign in with an
                    approved business account for wholesale and distributor pricing.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('parts.index') }}" class="rounded-md bg-neutral-900 px-5 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                        Shop Parts
                    </a>
                    <a href="{{ route('vin-lookup.show') }}" class="rounded-md border border-neutral-300 px-5 py-2.5 text-sm font-medium dark:border-neutral-700">
                        Look up a VIN
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-6 py-12">
        <h2 class="text-xl font-semibold">Shop by category</h2>
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($categories as $category)
                <a
                    href="{{ route('categories.show', $category) }}"
                    class="flex flex-col items-start gap-3 rounded-lg border border-neutral-200 p-4 transition hover:border-neutral-300 hover:shadow-sm dark:border-neutral-800 dark:hover:border-neutral-700"
                >
                    <x-category-icon :slug="$category->slug" class="size-7 text-neutral-500" />
                    <span class="font-medium">{{ $category->name }}</span>
                    <span class="text-xs text-neutral-500">{{ $category->parts_count }} parts</span>
                </a>
            @endforeach
        </div>
    </section>

    @if ($featuredParts->isNotEmpty())
        <section class="mx-auto max-w-7xl px-6 pb-16">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">Recently added</h2>
                <a href="{{ route('parts.index') }}" class="text-sm font-medium text-neutral-600 hover:underline dark:text-neutral-400">
                    View all parts →
                </a>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featuredParts as $part)
                    <x-part-card :part="$part" />
                @endforeach
            </div>
        </section>
    @endif
</x-layout>
