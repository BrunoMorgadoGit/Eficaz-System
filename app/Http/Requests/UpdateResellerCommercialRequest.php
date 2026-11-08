<?php

namespace App\Http\Requests;

use App\Models\Reseller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResellerCommercialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'commercial_profile' => ['required', Rule::in([
                Reseller::PROFILE_STANDARD,
                Reseller::PROFILE_GOLD,
                Reseller::PROFILE_PREMIUM,
            ])],
            'credit_limit' => ['present', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $creditLimit = trim((string) $this->input('credit_limit', ''));

        $attributes = [
            'company_name' => trim((string) $this->input('company_name')),
            'commercial_profile' => strtoupper(trim((string) $this->input('commercial_profile'))),
        ];

        if (array_key_exists('credit_limit', $this->all())) {
            $attributes['credit_limit'] = $creditLimit === '' ? null : str_replace(',', '.', $creditLimit);
        }

        $this->merge($attributes);
    }
}
