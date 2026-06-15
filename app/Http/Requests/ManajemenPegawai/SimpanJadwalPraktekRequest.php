<?php

namespace App\Http\Requests\ManajemenPegawai;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanJadwalPraktekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'kd_dokter' => [
                'required',
                'string',
                'max:20',
                Rule::exists('dokter', 'kd_dokter')->where(fn ($query) => $query->where('status', '1')),
            ],
            'hari_kerja' => ['required', Rule::in(['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU', 'AKHAD'])],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'date_format:H:i'],
            'kd_poli' => ['required', 'string', 'max:5', Rule::exists('poliklinik', 'kd_poli')],
            'kuota' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
