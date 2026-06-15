<?php

namespace App\Http\Requests\ManajemenUser;

use App\Models\ManajemenUser\GrupAuth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UbahGrupAuthRequest extends FormRequest
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
        $group = $this->route('authGroup');
        $groupId = $group instanceof GrupAuth ? $group->id : null;

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
                Rule::unique('uxui_auth_group', 'alias')
                    ->ignore($groupId),
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
