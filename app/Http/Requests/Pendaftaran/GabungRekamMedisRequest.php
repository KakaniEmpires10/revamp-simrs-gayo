<?php

namespace App\Http\Requests\Pendaftaran;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GabungRekamMedisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'no_rm_lama' => ['required', 'string', 'max:15', 'exists:pasien,no_rkm_medis'],
            'no_rm_baru' => [
                'required',
                'string',
                'max:15',
                'exists:pasien,no_rkm_medis',
                Rule::notIn([(string) $this->input('no_rm_lama')]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_rm_baru.not_in' => 'No RM baru harus berbeda dari No RM lama.',
        ];
    }
}
