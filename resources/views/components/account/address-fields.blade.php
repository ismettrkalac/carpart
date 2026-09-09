@props(['address' => null])

<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="label" class="block text-sm font-medium">Label (optional)</label>
        <input type="text" id="label" name="label" value="{{ old('label', $address?->label) }}" placeholder="Home, Office, …"
            class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
        @error('label')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="name" class="block text-sm font-medium">Full name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $address?->name) }}" required
            class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
        @error('name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="line1" class="block text-sm font-medium">Address</label>
        <input type="text" id="line1" name="line1" value="{{ old('line1', $address?->line1) }}" required
            class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
        @error('line1')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="line2" class="block text-sm font-medium">Apartment, suite, etc. (optional)</label>
        <input type="text" id="line2" name="line2" value="{{ old('line2', $address?->line2) }}"
            class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
        @error('line2')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="city" class="block text-sm font-medium">City</label>
        <input type="text" id="city" name="city" value="{{ old('city', $address?->city) }}" required
            class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
        @error('city')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="state" class="block text-sm font-medium">State / Province</label>
        <input type="text" id="state" name="state" value="{{ old('state', $address?->state) }}" required
            class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
        @error('state')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="postal_code" class="block text-sm font-medium">Postal code</label>
        <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $address?->postal_code) }}" required
            class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
        @error('postal_code')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="country" class="block text-sm font-medium">Country</label>
        <input type="text" id="country" name="country" value="{{ old('country', $address?->country) }}" required
            class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
        @error('country')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" name="is_default" value="1" {{ old('is_default', $address?->is_default) ? 'checked' : '' }}
                {{ $address?->is_default ? 'disabled' : '' }}
                class="rounded border-neutral-300">
            Make this my default address
        </label>
        @if ($address?->is_default)
            <p class="mt-1 text-xs text-neutral-500">This is already your default address.</p>
        @endif
    </div>
</div>
