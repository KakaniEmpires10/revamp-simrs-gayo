<?php

namespace App\Http\Requests\ManajemenPegawai;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanProfilPetugasRequest extends FormRequest
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
        $staff = $this->route('staff');

        return [
            'nip' => [
                'required',
                'string',
                'max:20',
                Rule::unique('petugas', 'nip')->ignore($staff, 'nip'),
                Rule::unique('pegawai', 'nik')->ignore($staff, 'nik'),
            ],
            'nama' => ['required', 'string', 'max:50'],
            'no_ktp' => ['nullable', 'string', 'max:20'],
            'jk' => ['required', Rule::in(['L', 'P'])],
            'tmp_lahir' => ['nullable', 'string', 'max:20'],
            'tgl_lahir' => ['nullable', 'date'],
            'gol_darah' => ['nullable', Rule::in(['A', 'B', 'O', 'AB', '-'])],
            'agama' => ['nullable', 'string', 'max:12'],
            'stts_nikah' => ['nullable', Rule::in(['BELUM MENIKAH', 'MENIKAH', 'JANDA', 'DUDHA', 'JOMBLO'])],
            'alamat' => ['nullable', 'string', 'max:60'],
            'kd_jbtn' => ['required', 'string', 'max:4', Rule::exists('jabatan', 'kd_jbtn')],
            'no_telp' => ['nullable', 'string', 'max:13'],
            'status' => ['required', Rule::in(['0', '1'])],
        ];
    }
}
