<x-layout title="Your Profile">
    <div class="mx-auto max-w-3xl px-6 py-10">
        <h1 class="text-2xl font-semibold">Your Profile</h1>
        <p class="mt-1 text-neutral-500">{{ $user->name }} · {{ $user->email }}</p>

        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <a
                href="{{ route('account.addresses.index') }}"
                class="group flex items-center justify-between rounded-lg border border-neutral-200 p-6 hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700"
            >
                <div>
                    <h2 class="font-semibold">Addresses</h2>
                    <p class="mt-1 text-sm text-neutral-500">{{ $addressCount }} {{ \Illuminate\Support\Str::plural('address', $addressCount) }} saved</p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 shrink-0 text-neutral-400 group-hover:text-neutral-600 dark:group-hover:text-neutral-300">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/>
                </svg>
            </a>

            <a
                href="{{ route('account.orders.index') }}"
                class="group flex items-center justify-between rounded-lg border border-neutral-200 p-6 hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700"
            >
                <div>
                    <h2 class="font-semibold">Orders</h2>
                    <p class="mt-1 text-sm text-neutral-500">{{ $orderCount }} {{ \Illuminate\Support\Str::plural('order', $orderCount) }} placed</p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 shrink-0 text-neutral-400 group-hover:text-neutral-600 dark:group-hover:text-neutral-300">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</x-layout>
