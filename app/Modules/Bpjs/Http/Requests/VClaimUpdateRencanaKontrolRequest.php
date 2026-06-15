<?php

namespace App\Modules\Bpjs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VClaimUpdateRencanaKontrolRequest extends FormRequest
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
            'no_sep' => ['required', 'string', 'max:40'],
            'kode_dokter' => ['required', 'string', 'max:30'],
            'nama_dokter' => ['required', 'string', 'max:100'],
            'poli_kontrol' => ['required', 'string', 'max:20'],
            'nama_poli' => ['required', 'string', 'max:100'],
            'tanggal_kontrol' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
