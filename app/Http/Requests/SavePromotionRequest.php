<?php

namespace App\Http\Requests;

use App\Models\Reseller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'commercial_profile' => ['required', Rule::in([
                Reseller::PROFILE_STANDARD,
                Reseller::PROFILE_GOLD,
                Reseller::PROFILE_PREMIUM,
            ])],
            'discount_percentage' => ['required', 'integer', 'min:1', 'max:100'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'is_active' => ['required', 'boolean'],
            'is_stackable' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'commercial_profile' => strtoupper(trim((string) $this->input('commercial_profile'))),
            'is_active' => $this->boolean('is_active'),
            'is_stackable' => $this->boolean('is_stackable'),
        ]);
    }
}
