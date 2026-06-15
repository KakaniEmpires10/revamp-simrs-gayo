<?php

namespace App\Http\Requests\Pendaftaran;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanDataPasienRequest extends FormRequest
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
            'auto_no_rm' => ['required', 'boolean'],
            'no_rkm_medis' => ['nullable', 'required_if:auto_no_rm,false', 'string', 'min:5', 'max:15', 'unique:pasien,no_rkm_medis'],
            'nm_pasien' => ['required', 'string', 'max:40'],
            'jk' => ['required', Rule::in(['L', 'P'])],
            'tmp_lahir' => ['required', 'string', 'max:30'],
            'tgl_lahir' => ['required', 'date', 'before_or_equal:today'],
            'nm_ibu' => ['required', 'string', 'max:40'],
            'alamat' => ['required', 'string', 'max:200'],
            'no_ktp' => ['required', 'string', 'max:20'],
            'no_tlp' => ['required', 'string', 'max:40'],
            'kd_pj' => ['required', 'string', 'max:3', 'exists:penjab,kd_pj'],
            'agama' => ['required', 'string', 'max:12'],
            'stts_nikah' => ['required', Rule::in(['BELUM MENIKAH', 'MENIKAH', 'JANDA', 'DUDHA', 'JOMBLO'])],
            'gol_darah' => ['nullable', Rule::in(['A', 'B', 'O', 'AB', '-'])],
            'pnd' => ['nullable', Rule::in(['TS', 'TK', 'SD', 'SMP', 'SMA', 'SLTA/SEDERAJAT', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3', '-'])],
            'pekerjaan' => ['nullable', 'string', 'max:60'],
            'no_peserta' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:50'],
            'keluarga' => ['nullable', Rule::in(['AYAH', 'IBU', 'ISTRI', 'SUAMI', 'SAUDARA', 'ANAK'])],
            'namakeluarga' => ['nullable', 'string', 'max:50'],
            'pekerjaanpj' => ['nullable', 'string', 'max:35'],
            'alamatpj' => ['nullable', 'string', 'max:100'],
            'kelurahanpj' => ['nullable', 'string', 'max:60'],
            'kecamatanpj' => ['nullable', 'string', 'max:60'],
            'kabupatenpj' => ['nullable', 'string', 'max:60'],
            'kd_kel' => ['nullable', 'integer'],
            'kd_kec' => ['nullable', 'integer'],
            'kd_kab' => ['nullable', 'integer'],
            'kd_prop' => ['nullable', 'integer'],
            'cacat_fisik' => ['nullable', 'integer'],
            'suku_bangsa' => ['nullable', 'integer'],
            'bahasa_pasien' => ['nullable', 'integer'],
            'perusahaan_pasien' => ['nullable', 'string', 'max:8'],
        ];
    }
}
