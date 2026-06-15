<?php

namespace App\Http\Requests\ManajemenUser;

use App\Models\ManajemenUser\GrupAuth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanGrupAuthRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge([
                'alias' => GrupAuth::aliasFromName((string) $this->input('name')),
            ]);
        }
    }

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
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'keterangan' => [
                'nullable',
                'string',
                'max:255',
            ],
            'alias' => [
                'required',
                'string',
                'max:100',
                Rule::unique('uxui_auth_group', 'alias'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama level akses wajib diisi.',
            'alias.unique' => 'Level akses dengan nama tersebut sudah terdaftar.',
        ];
    }
}
