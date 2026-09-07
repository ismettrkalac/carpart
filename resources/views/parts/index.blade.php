@php
    $withoutQuery = fn (string ...$keys) => http_build_query(collect(request()->query())->except($keys)->all());
@endphp

<x-layout :title="$category?->name ?? 'Shop Parts'">
    <div class="mx-auto max-w-7xl px-6 py-10">
        <nav class="text-sm text-neutral-500">
            <a href="{{ route('home') }}" class="hover:underline">Home</a>
            <span class="mx-1">/</span>
            @if ($category)
                <a href="{{ route('parts.index') }}" class="hover:underline">Shop Parts</a>
                <span class="mx-1">/</span>
                <span class="text-neutral-900 dark:text-white">{{ $category->name }}</span>
            @else
                <span class="text-neutral-900 dark:text-white">Shop Parts</span>
            @endif
        </nav>

        <h1 class="mt-2 text-2xl font-semibold">{{ $category?->name ?? 'Shop Parts' }}</h1>

        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[240px_1fr]">
            <aside class="space-y-8">
                <form method="GET" action="{{ request()->url() }}">
                    @if ($sort !== 'newest')
                        <input type="hidden" name="sort" value="{{ $sort }}">
                    @endif
                    @if ($activeManufacturer)
                        <input type="hidden" name="manufacturer" value="{{ $activeManufacturer->slug }}">
                    @endif

                    <label for="q" class="text-sm font-medium">Search</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        value="{{ $query }}"
                        placeholder="Name, SKU, description…"
                        x-on:input.debounce.500ms="$el.form.requestSubmit()"
                        class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                    >

                    <noscript><button type="submit" class="mt-2 text-sm underline">Search</button></noscript>
                </form>

                <div>
                    <h2 class="text-sm font-medium">Category</h2>
                    <ul class="mt-2 space-y-1 text-sm">
                        <li>
                            <a
                                href="{{ route('parts.index', request()->except('page')) }}"
                                class="{{ ! $category ? 'font-medium text-neutral-900 dark:text-white' : 'text-neutral-600 dark:text-neutral-400' }} hover:underline"
                            >
                                All categories
                            </a>
                        </li>
                        @foreach ($categories as $cat)
                            <li>
                                <a
                                    href="{{ route('categories.show', array_merge(['category' => $cat->slug], request()->except(['page']))) }}"
                                    class="{{ $category?->id === $cat->id ? 'font-medium text-neutral-900 dark:text-white' : 'text-neutral-600 dark:text-neutral-400' }} hover:underline"
                                >
                                    {{ $cat->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <label for="manufacturer" class="text-sm font-medium">Manufacturer</label>
                    <form method="GET" action="{{ request()->url() }}">
                        @if ($query)
                            <input type="hidden" name="q" value="{{ $query }}">
                        @endif
                        @if ($sort !== 'newest')
                            <input type="hidden" name="sort" value="{{ $sort }}">
                        @endif
                        <select
                            id="manufacturer"
                            name="manufacturer"
                            x-on:change="$el.form.submit()"
                            class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                        >
                            <option value="">All manufacturers</option>
                            @foreach ($manufacturers as $manufacturer)
                                <option value="{{ $manufacturer->slug }}" @selected($activeManufacturer?->id === $manufacturer->id)>
                                    {{ $manufacturer->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </aside>

            <div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($query)
                            <span class="inline-flex items-center gap-1 rounded-full bg-neutral-100 px-3 py-1 text-xs dark:bg-neutral-900">
                                “{{ $query }}”
                                <a href="?{{ $withoutQuery('q', 'page') }}" aria-label="Clear search">✕</a>
                            </span>
                        @endif
                        @if ($activeManufacturer)
                            <span class="inline-flex items-center gap-1 rounded-full bg-neutral-100 px-3 py-1 text-xs dark:bg-neutral-900">
                                {{ $activeManufacturer->name }}
                                <a href="?{{ $withoutQuery('manufacturer', 'page') }}" aria-label="Clear manufacturer filter">✕</a>
                            </span>
                        @endif
                    </div>

                    <form method="GET" action="{{ request()->url() }}" class="flex items-center gap-2 text-sm">
                        @if ($query)
                            <input type="hidden" name="q" value="{{ $query }}">
                        @endif
                        @if ($activeManufacturer)
                            <input type="hidden" name="manufacturer" value="{{ $activeManufacturer->slug }}">
                        @endif
                        <label for="sort" class="text-neutral-500">Sort by</label>
                        <select
                            id="sort"
                            name="sort"
                            x-on:change="$el.form.submit()"
                            class="rounded-md border border-neutral-300 px-2 py-1.5 dark:border-neutral-700 dark:bg-neutral-900"
                        >
                            <option value="newest" @selected($sort === 'newest')>Newest</option>
                            <option value="name" @selected($sort === 'name')>Name</option>
                            <option value="price_asc" @selected($sort === 'price_asc')>Price: low to high</option>
                            <option value="price_desc" @selected($sort === 'price_desc')>Price: high to low</option>
                        </select>
                    </form>
                </div>

                @if ($parts->isEmpty())
                    <div class="mt-16 text-center text-neutral-500">
                        <p class="font-medium">No parts match your filters.</p>
                        <p class="mt-1 text-sm">Try a different search term or clear the filters above.</p>
                    </div>
                @else
                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($parts as $part)
                            <x-part-card :part="$part" />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $parts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layout>
