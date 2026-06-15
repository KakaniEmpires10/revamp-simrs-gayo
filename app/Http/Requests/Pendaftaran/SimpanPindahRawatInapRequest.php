<?php

namespace App\Http\Requests\Pendaftaran;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanPindahRawatInapRequest extends FormRequest
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
            'kd_kamar' => ['required', 'string', 'max:15'],
            'kd_dokter' => ['required', 'string', 'max:20'],
            'tgl_masuk' => ['required', 'date'],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'diagnosa_awal' => ['required', 'string', 'max:100'],
            'kd_penyakit' => ['nullable', 'string', 'max:10'],
            'mode_diagnosa' => ['required', Rule::in(['referensi', 'manual'])],
        ];
    }
}
