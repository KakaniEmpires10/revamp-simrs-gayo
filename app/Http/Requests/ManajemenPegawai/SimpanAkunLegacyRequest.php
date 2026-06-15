<?php

namespace App\Http\Requests\ManajemenPegawai;

use Illuminate\Foundation\Http\FormRequest;

class SimpanAkunLegacyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'id_user' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_user.required' => 'ID user wajib dipilih.',
            'password.required' => 'Password wajib diisi.',
        ];
    }
}
