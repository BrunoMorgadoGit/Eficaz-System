<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['PENDENTE', 'APROVADO', 'CONCLUIDO'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => strtoupper(trim((string) $this->input('status'))),
        ]);
    }
}
