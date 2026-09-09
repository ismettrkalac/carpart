<x-layout title="Add Address">
    <div class="mx-auto max-w-2xl px-6 py-10">
        <a href="{{ route('account.addresses.index') }}" class="text-sm text-neutral-500 hover:underline">&larr; Your addresses</a>
        <h1 class="mt-2 text-2xl font-semibold">Add Address</h1>

        <form method="POST" action="{{ route('account.addresses.store') }}">
            @csrf

            <x-account.address-fields :address="null" />

            <button type="submit" class="mt-6 rounded-md bg-neutral-900 px-5 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                Save address
            </button>
        </form>
    </div>
</x-layout>
