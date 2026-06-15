<?php

namespace App\Modules\Bpjs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VClaimCekRujukanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $identifierType = $this->input('identifier_type', 'card');

        return [
            'identifier_type' => ['nullable', Rule::in(['card', 'nik'])],
            'identifier' => [
                'nullable',
                'digits:'.($identifierType === 'nik' ? '16' : '13'),
                Rule::when($this->filled('identifier'), ['required']),
            ],
            'source' => ['nullable', Rule::in(['pcare', 'rs'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'identifier_type' => $this->input('identifier_type', 'card'),
            'identifier' => preg_replace('/\D/', '', (string) $this->input('identifier', '')),
        ]);
    }
}
