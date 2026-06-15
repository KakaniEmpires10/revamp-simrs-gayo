<?php

namespace App\Modules\Bpjs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VClaimStoreRencanaRawatInapRequest extends FormRequest
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
            'no_kartu' => ['required', 'digits:13'],
            'kode_dokter' => ['required', 'string', 'max:30'],
            'poli_kontrol' => ['required', 'string', 'max:20'],
            'tanggal_kontrol' => ['required', 'date_format:Y-m-d'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'no_kartu' => preg_replace('/\D/', '', (string) $this->input('no_kartu', '')),
        ]);
    }
}
