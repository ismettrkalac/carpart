@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('icon.png') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-white text-neutral-900 antialiased dark:bg-neutral-950 dark:text-neutral-100">
        <div x-data="{ mobileMenuOpen: false }" class="contents">
            <header class="border-b border-neutral-200 dark:border-neutral-800">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-semibold">
                        <img src="{{ asset('logo.png') }}" alt="" class="size-8" width="32" height="32">
                        {{ config('app.name') }}
                    </a>

                    <nav class="hidden items-center gap-6 text-sm font-medium md:flex">
                        <a href="{{ route('home') }}" class="hover:text-neutral-500 {{ request()->routeIs('home') ? 'text-neutral-900 dark:text-white' : 'text-neutral-600 dark:text-neutral-400' }}">Home</a>
                        <a href="{{ route('parts.index') }}" class="hover:text-neutral-500 {{ request()->routeIs('parts.*') || request()->routeIs('categories.*') ? 'text-neutral-900 dark:text-white' : 'text-neutral-600 dark:text-neutral-400' }}">Shop Parts</a>
                        <a href="{{ route('vin-lookup.show') }}" class="hover:text-neutral-500 {{ request()->routeIs('vin-lookup.*') ? 'text-neutral-900 dark:text-white' : 'text-neutral-600 dark:text-neutral-400' }}">VIN Lookup</a>
                    </nav>

                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden"
                        aria-label="Toggle menu"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-6">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
                            <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <nav x-show="mobileMenuOpen" x-cloak class="flex flex-col gap-1 border-t border-neutral-200 px-6 py-3 text-sm font-medium md:hidden dark:border-neutral-800">
                    <a href="{{ route('home') }}" class="rounded-md px-2 py-2 hover:bg-neutral-100 dark:hover:bg-neutral-900">Home</a>
                    <a href="{{ route('parts.index') }}" class="rounded-md px-2 py-2 hover:bg-neutral-100 dark:hover:bg-neutral-900">Shop Parts</a>
                    <a href="{{ route('vin-lookup.show') }}" class="rounded-md px-2 py-2 hover:bg-neutral-100 dark:hover:bg-neutral-900">VIN Lookup</a>
                </nav>
            </header>

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="border-t border-neutral-200 dark:border-neutral-800">
                <div class="mx-auto max-w-7xl px-6 py-8 text-sm text-neutral-500">
                    <p>{{ config('app.name') }} — parts catalog for businesses. Demo data only.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
