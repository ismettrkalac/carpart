<x-layout title="Checkout">
    <div class="mx-auto max-w-5xl px-6 py-10">
        <h1 class="text-2xl font-semibold">Checkout</h1>

        @if (! empty($changes))
            <div class="mt-6 rounded-md bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                <p class="font-medium">Your order changed — please review before placing it.</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($changes as $change)
                        <li>{{ $change }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('checkout.store') }}"
            class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[1fr_360px]"
            x-data="{ billingDifferent: {{ old('billing_different', $prefill['billing_different'] ?? false) ? 'true' : 'false' }}, submitting: false }"
            x-on:submit="submitting = true"
        >
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

            <div class="space-y-8">
                <section>
                    <h2 class="font-semibold">Contact</h2>
                    <div class="mt-3">
                        <label for="email" class="block text-sm font-medium">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $prefill['email'] ?? '') }}" required
                            class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <section>
                    <h2 class="font-semibold">Shipping address</h2>
                    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="shipping_name" class="block text-sm font-medium">Full name</label>
                            <input type="text" id="shipping_name" name="shipping_name" value="{{ old('shipping_name', $prefill['shipping_name'] ?? '') }}" required
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                            @error('shipping_name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="shipping_line1" class="block text-sm font-medium">Address</label>
                            <input type="text" id="shipping_line1" name="shipping_line1" value="{{ old('shipping_line1', $prefill['shipping_line1'] ?? '') }}" required
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                            @error('shipping_line1')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="shipping_line2" class="block text-sm font-medium">Apartment, suite, etc. (optional)</label>
                            <input type="text" id="shipping_line2" name="shipping_line2" value="{{ old('shipping_line2', $prefill['shipping_line2'] ?? '') }}"
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                        </div>
                        <div>
                            <label for="shipping_city" class="block text-sm font-medium">City</label>
                            <input type="text" id="shipping_city" name="shipping_city" value="{{ old('shipping_city', $prefill['shipping_city'] ?? '') }}" required
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                            @error('shipping_city')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="shipping_state" class="block text-sm font-medium">State / Province</label>
                            <input type="text" id="shipping_state" name="shipping_state" value="{{ old('shipping_state', $prefill['shipping_state'] ?? '') }}" required
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                            @error('shipping_state')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="shipping_postal_code" class="block text-sm font-medium">Postal code</label>
                            <input type="text" id="shipping_postal_code" name="shipping_postal_code" value="{{ old('shipping_postal_code', $prefill['shipping_postal_code'] ?? '') }}" required
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                            @error('shipping_postal_code')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="shipping_country" class="block text-sm font-medium">Country</label>
                            <input type="text" id="shipping_country" name="shipping_country" value="{{ old('shipping_country', $prefill['shipping_country'] ?? '') }}" required
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                            @error('shipping_country')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    @auth
                        <label class="mt-4 flex items-center gap-2 text-sm font-medium">
                            <input type="checkbox" name="save_address" value="1" {{ old('save_address', true) ? 'checked' : '' }} class="rounded border-neutral-300">
                            Save this address to my account
                        </label>
                    @endauth
                </section>

                <section>
                    <label class="flex items-center gap-2 text-sm font-medium">
                        <input type="checkbox" name="billing_different" value="1" x-model="billingDifferent" class="rounded border-neutral-300">
                        Billing address is different from shipping
                    </label>

                    <div x-show="billingDifferent" x-cloak class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="billing_name" class="block text-sm font-medium">Full name</label>
                            <input type="text" id="billing_name" name="billing_name" value="{{ old('billing_name', $prefill['billing_name'] ?? '') }}"
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                            @error('billing_name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="billing_line1" class="block text-sm font-medium">Address</label>
                            <input type="text" id="billing_line1" name="billing_line1" value="{{ old('billing_line1', $prefill['billing_line1'] ?? '') }}"
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                            @error('billing_line1')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="billing_line2" class="block text-sm font-medium">Apartment, suite, etc. (optional)</label>
                            <input type="text" id="billing_line2" name="billing_line2" value="{{ old('billing_line2', $prefill['billing_line2'] ?? '') }}"
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                        </div>
                        <div>
                            <label for="billing_city" class="block text-sm font-medium">City</label>
                            <input type="text" id="billing_city" name="billing_city" value="{{ old('billing_city', $prefill['billing_city'] ?? '') }}"
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                            @error('billing_city')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="billing_state" class="block text-sm font-medium">State / Province</label>
                            <input type="text" id="billing_state" name="billing_state" value="{{ old('billing_state', $prefill['billing_state'] ?? '') }}"
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                            @error('billing_state')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="billing_postal_code" class="block text-sm font-medium">Postal code</label>
                            <input type="text" id="billing_postal_code" name="billing_postal_code" value="{{ old('billing_postal_code', $prefill['billing_postal_code'] ?? '') }}"
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                            @error('billing_postal_code')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="billing_country" class="block text-sm font-medium">Country</label>
                            <input type="text" id="billing_country" name="billing_country" value="{{ old('billing_country', $prefill['billing_country'] ?? '') }}"
                                class="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900">
                            @error('billing_country')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>
            </div>

            <div class="h-fit space-y-4 rounded-lg border border-neutral-200 p-5 dark:border-neutral-800">
                <h2 class="font-semibold">Order summary</h2>

                <ul class="space-y-3 text-sm">
                    @foreach ($items as $item)
                        <li class="flex justify-between gap-3">
                            <span class="text-neutral-600 dark:text-neutral-400">
                                {{ $item->part?->name ?? 'Item' }} × {{ $item->quantity }}
                            </span>
                            <span class="whitespace-nowrap font-medium">{{ $item->formattedLineTotal() }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="space-y-1.5 border-t border-neutral-200 pt-3 text-sm dark:border-neutral-800">
                    <div class="flex justify-between">
                        <span class="text-neutral-500">Subtotal</span>
                        <span>{{ $totals->formattedSubtotal() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500">Shipping</span>
                        <span>{{ $totals->formattedShipping() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500">Tax</span>
                        <span>{{ $totals->formattedTax() }}</span>
                    </div>
                    <div class="flex justify-between border-t border-neutral-200 pt-1.5 text-base font-semibold dark:border-neutral-800">
                        <span>Total</span>
                        <span>{{ $totals->formattedTotal() }}</span>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="submitting"
                    class="w-full rounded-md bg-neutral-900 py-2.5 text-sm font-medium text-white disabled:opacity-60 dark:bg-white dark:text-neutral-900"
                >
                    <span x-show="!submitting">Place Order</span>
                    <span x-show="submitting" x-cloak>Placing order…</span>
                </button>

                <p class="text-xs text-neutral-500">
                    Your order is placed as pending payment; you may be redirected to complete payment next.
                </p>
            </div>
        </form>
    </div>
</x-layout>
