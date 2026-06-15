<?php

namespace App\Modules\Bpjs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VClaimDataFingerprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
