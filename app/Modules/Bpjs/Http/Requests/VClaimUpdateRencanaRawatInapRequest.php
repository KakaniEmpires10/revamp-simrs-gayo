<?php

namespace App\Modules\Bpjs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VClaimUpdateRencanaRawatInapRequest extends FormRequest
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
            'poli_kontrol' => ['required', 'string', 'max:20'],
            'tanggal_kontrol' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
