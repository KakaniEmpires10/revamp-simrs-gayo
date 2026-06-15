<?php

namespace App\Http\Requests\ManajemenPegawai;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanProfilDokterRequest extends FormRequest
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
        $doctor = $this->route('doctor');

        return [
            'kd_dokter' => [
                'required',
                'string',
                'max:20',
                Rule::unique('dokter', 'kd_dokter')->ignore($doctor, 'kd_dokter'),
                Rule::unique('pegawai', 'nik')->ignore($doctor, 'nik'),
            ],
            'nm_dokter' => ['required', 'string', 'max:50'],
            'no_ktp' => ['nullable', 'string', 'max:20'],
            'jk' => ['required', Rule::in(['L', 'P'])],
            'tmp_lahir' => ['nullable', 'string', 'max:20'],
            'tgl_lahir' => ['nullable', 'date'],
            'gol_drh' => ['nullable', Rule::in(['A', 'B', 'O', 'AB', '-'])],
            'agama' => ['nullable', 'string', 'max:12'],
            'almt_tgl' => ['nullable', 'string', 'max:60'],
            'no_telp' => ['nullable', 'string', 'max:13'],
            'stts_nikah' => ['nullable', Rule::in(['BELUM MENIKAH', 'MENIKAH', 'JANDA', 'DUDHA', 'JOMBLO'])],
            'kd_sps' => ['required', 'string', 'max:5', Rule::exists('spesialis', 'kd_sps')],
            'alumni' => ['nullable', 'string', 'max:60'],
            'no_nip' => ['nullable', 'string', 'max:50'],
            'no_ijn_praktek' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['0', '1'])],
        ];
    }
}
