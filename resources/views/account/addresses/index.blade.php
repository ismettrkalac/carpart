<x-layout title="Your Addresses">
    <div class="mx-auto max-w-3xl px-6 py-10">
        <a href="{{ route('account.profile') }}" class="text-sm text-neutral-500 hover:underline">&larr; Profile</a>

        <div class="mt-2 flex items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">Your Addresses</h1>
            <a href="{{ route('account.addresses.create') }}" class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-neutral-900">
                Add address
            </a>
        </div>

        @if ($addresses->isEmpty())
            <div class="mt-16 text-center text-neutral-500">
                <p class="font-medium">You haven't saved any addresses yet.</p>
                <p class="mt-1 text-sm">Save one to skip retyping it at checkout next time.</p>
            </div>
        @else
            <div class="mt-8 space-y-4">
                @foreach ($addresses as $address)
                    <div class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-medium">{{ $address->label ?: $address->name }}</p>
                                    @if ($address->is_default)
                                        <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium dark:bg-neutral-900">Default</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-neutral-500">
                                    {{ $address->name }}<br>
                                    {{ $address->line1 }}@if ($address->line2), {{ $address->line2 }}@endif<br>
                                    {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}<br>
                                    {{ $address->country }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3 text-sm">
                                <a href="{{ route('account.addresses.edit', $address) }}" class="font-medium hover:underline">Edit</a>
                                <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" onsubmit="return confirm('Remove this address?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-neutral-500 hover:text-red-600">Remove</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
