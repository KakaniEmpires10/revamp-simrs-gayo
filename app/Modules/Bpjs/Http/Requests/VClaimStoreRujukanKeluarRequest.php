<?php

namespace App\Modules\Bpjs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VClaimStoreRujukanKeluarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'no_sep' => ['required', 'string', 'max:20'],
            'tanggal_rujukan' => ['required', 'date_format:Y-m-d'],
            'tanggal_rencana_kunjungan' => ['required', 'date_format:Y-m-d'],
            'ppk_dirujuk' => ['required', 'string', 'max:30'],
            'nama_ppk_dirujuk' => ['required', 'string', 'max:100'],
            'jenis_pelayanan' => ['required', Rule::in(['1', '2'])],
            'catatan' => ['nullable', 'string', 'max:255'],
            'diagnosa_rujukan' => ['required', 'string', 'max:20'],
            'nama_diagnosa_rujukan' => ['required', 'string', 'max:400'],
            'tipe_rujukan' => ['required', Rule::in(['0', '1', '2'])],
            'poli_rujukan' => ['required', 'string', 'max:20'],
            'nama_poli_rujukan' => ['required', 'string', 'max:100'],
        ];
    }
}
