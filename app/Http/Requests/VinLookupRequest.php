<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\ViewErrorBag;

class VinLookupRequest extends FormRequest
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
            // 17 characters, uppercase alphanumeric, excluding I/O/Q (never used in VINs).
            'vin' => ['nullable', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/'],
            'modelyear' => ['nullable', 'integer', 'min:1981', 'max:'.(now()->year + 1)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vin.size' => 'A VIN is exactly 17 characters.',
            'vin.regex' => 'That doesn\'t look like a valid VIN — the letters I, O and Q are never used.',
            'modelyear.min' => 'Model year must be 1981 or later (the year VINs were standardized).',
        ];
    }

    /**
     * Normalize the VIN before validation runs.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('vin')) {
            $this->merge(['vin' => strtoupper(trim((string) $this->input('vin')))]);
        }
    }

    /**
     * This is a GET-based search form, not a POST form, so there's no
     * reliable "previous page" to redirect back to (especially on a
     * fresh visit). Re-render the same page directly instead.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = new ViewErrorBag;
        $errors->put('default', $validator->errors());

        throw new HttpResponseException(
            response()->view('vin-lookup.show', [
                'vin' => $this->input('vin'),
                'errors' => $errors,
            ], 422)
        );
    }
}
