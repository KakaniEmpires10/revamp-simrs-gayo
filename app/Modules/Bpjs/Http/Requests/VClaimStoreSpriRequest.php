<?php

namespace App\Modules\Bpjs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VClaimStoreSpriRequest extends FormRequest
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
            'kode_dokter' => ['required', 'string', 'max:30'],
            'nama_dokter' => ['nullable', 'string', 'max:100'],
            'poli_kontrol' => ['required', 'string', 'max:20'],
            'nama_poli' => ['nullable', 'string', 'max:100'],
            'tanggal_kontrol' => ['required', 'date_format:Y-m-d'],
            'diagnosa_awal' => ['required', 'string', 'max:130'],
            'kd_penyakit' => ['nullable', 'string', 'max:10'],
            'mode_diagnosa' => ['required', 'in:manual,referensi'],
            'no_sep' => ['nullable', 'string', 'max:40'],
        ];
    }
}
