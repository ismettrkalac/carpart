<?php

namespace App\Http\Requests;

use App\Services\Checkout\Address;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],

            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_line1' => ['required', 'string', 'max:255'],
            'shipping_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:255'],
            'shipping_state' => ['required', 'string', 'max:100'],
            'shipping_postal_code' => ['required', 'string', 'max:20'],
            'shipping_country' => ['required', 'string', 'max:100'],

            // Checked "different billing address" checkbox — absent (not
            // "false") when unchecked, which is why required_if compares
            // against the string "1" rather than a boolean.
            'billing_different' => ['nullable', 'boolean'],
            'billing_name' => ['required_if:billing_different,1', 'nullable', 'string', 'max:255'],
            'billing_line1' => ['required_if:billing_different,1', 'nullable', 'string', 'max:255'],
            'billing_line2' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['required_if:billing_different,1', 'nullable', 'string', 'max:255'],
            'billing_state' => ['required_if:billing_different,1', 'nullable', 'string', 'max:100'],
            'billing_postal_code' => ['required_if:billing_different,1', 'nullable', 'string', 'max:20'],
            'billing_country' => ['required_if:billing_different,1', 'nullable', 'string', 'max:100'],

            // Idempotency key minted when the checkout page rendered;
            // see CheckoutSnapshot / CheckoutController.
            'checkout_token' => ['required', 'string'],
        ];
    }

    public function shippingAddress(): Address
    {
        return new Address(
            name: $this->string('shipping_name')->toString(),
            line1: $this->string('shipping_line1')->toString(),
            line2: $this->filled('shipping_line2') ? $this->string('shipping_line2')->toString() : null,
            city: $this->string('shipping_city')->toString(),
            state: $this->string('shipping_state')->toString(),
            postalCode: $this->string('shipping_postal_code')->toString(),
            country: $this->string('shipping_country')->toString(),
        );
    }

    public function billingAddress(): Address
    {
        if (! $this->boolean('billing_different')) {
            return $this->shippingAddress();
        }

        return new Address(
            name: $this->string('billing_name')->toString(),
            line1: $this->string('billing_line1')->toString(),
            line2: $this->filled('billing_line2') ? $this->string('billing_line2')->toString() : null,
            city: $this->string('billing_city')->toString(),
            state: $this->string('billing_state')->toString(),
            postalCode: $this->string('billing_postal_code')->toString(),
            country: $this->string('billing_country')->toString(),
        );
    }
}
