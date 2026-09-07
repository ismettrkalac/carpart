<x-layout title="VIN Lookup">
    <div class="mx-auto max-w-2xl px-6 py-12">
        <h1 class="text-2xl font-semibold">VIN Lookup</h1>
        <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
            Decode a Vehicle Identification Number using the free NHTSA vPIC database. This identifies the
            vehicle only (make, model, year, engine, fuel) — it is not connected to our parts catalog, and it
            is not a guarantee of part compatibility.
        </p>

        <form method="GET" action="{{ route('vin-lookup.show') }}" class="mt-8 space-y-4">
            <div>
                <label for="vin" class="block text-sm font-medium">VIN</label>
                <input
                    type="text"
                    id="vin"
                    name="vin"
                    value="{{ old('vin', $vin ?? '') }}"
                    maxlength="17"
                    placeholder="e.g. 1HGCM82633A004352"
                    class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 font-mono uppercase tracking-wider dark:border-neutral-700 dark:bg-neutral-900"
                >
                @error('vin')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="modelyear" class="block text-sm font-medium">Model year (optional)</label>
                <input
                    type="number"
                    id="modelyear"
                    name="modelyear"
                    value="{{ old('modelyear', request('modelyear')) }}"
                    min="1981"
                    max="{{ now()->year + 1 }}"
                    placeholder="e.g. 2003"
                    class="mt-1 block w-40 rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900"
                >
                <p class="mt-1 text-xs text-neutral-500">
                    Supplying the model year helps vPIC disambiguate the 10th VIN character.
                </p>
                @error('modelyear')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-neutral-900"
            >
                Decode VIN
            </button>
        </form>

        @isset($lookupError)
            <div class="mt-8 rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                {{ $lookupError }}
            </div>
        @endisset

        @isset($vehicle)
            <div class="mt-8 rounded-md border border-neutral-200 p-4 dark:border-neutral-800">
                <h2 class="text-lg font-semibold">Results for {{ $vehicle->vin }}</h2>

                @if ($vehicle->hasNoData())
                    <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                        vPIC could not identify this VIN. This can happen with VINs outside the U.S. market,
                        pre-1981 VINs, or a mistyped character.
                    </p>
                @else
                    <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                        @foreach ($vehicle->displayFields() as $label => $value)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-neutral-500">{{ $label }}</dt>
                                <dd class="text-sm {{ $value === 'Unknown' ? 'text-neutral-400 italic' : '' }}">
                                    {{ $value }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                @unless ($vehicle->isFullyDecoded())
                    <div class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                        <p class="font-medium">Decoded with warnings — some fields may be missing or unreliable.</p>
                        <p class="mt-1">{{ $vehicle->errorText }}</p>
                        @foreach ($vehicle->additionalErrorMessages as $message)
                            <p class="mt-1">{{ $message }}</p>
                        @endforeach
                    </div>
                @endunless

                <p class="mt-4 text-xs text-neutral-500">
                    Vehicle data from NHTSA vPIC. Not linked to our parts catalog — nothing here implies a
                    part in our store is verified compatible with this vehicle.
                </p>
            </div>
        @endisset
    </div>
</x-layout>
