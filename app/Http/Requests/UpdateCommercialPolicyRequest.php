<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommercialPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'discount_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'payment_terms' => ['present', 'nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! array_key_exists('payment_terms', $this->all())) {
            return;
        }

        $terms = trim((string) $this->input('payment_terms'));

        $this->merge([
            'payment_terms' => $terms === '' ? null : $terms,
        ]);
    }
}
