<?php

namespace App\Http\Requests\ManajemenUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UbahAksesUserRequest extends FormRequest
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
        return [
            'alias_group' => [
                'required',
                'string',
                'max:100',
                Rule::exists('uxui_auth_group', 'alias'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alias_group.required' => 'Level akses wajib dipilih.',
        ];
    }
}
