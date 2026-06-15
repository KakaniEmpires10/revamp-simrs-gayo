<?php

namespace App\Http\Requests\Pendaftaran;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UbahPendaftaranUmumRequest extends FormRequest
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
            'jenis_pendaftaran' => ['required', Rule::in(['rawat_jalan', 'igd'])],
            'tgl_registrasi' => ['required', 'date'],
            'jam_reg' => ['required', 'date_format:H:i'],
            'kd_dokter' => ['required', 'string', 'max:20'],
            'kd_poli' => ['required_if:jenis_pendaftaran,rawat_jalan', 'nullable', 'string', 'max:5'],
            'kd_pj' => ['required', 'string', 'max:3'],
            'p_jawab' => ['required', 'string', 'max:100'],
            'almt_pj' => ['required', 'string', 'max:200'],
            'hubunganpj' => ['required', 'string', 'max:20'],
            'perujuk' => ['nullable', 'string', 'max:60'],
            'kategori_rujuk' => ['nullable', Rule::in(['-', 'Bedah', 'Non-Bedah', 'Kebidanan', 'Anak'])],
        ];
    }
}
