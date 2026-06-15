<?php

namespace App\Http\Requests\Pendaftaran;

use Illuminate\Foundation\Http\FormRequest;

class SimpanRujukanInternalRequest extends FormRequest
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
            'kd_poli' => ['required', 'string', 'max:5'],
            'kd_dokter' => ['required', 'string', 'max:20'],
        ];
    }
}
