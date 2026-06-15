<?php

namespace App\Http\Requests\ManajemenUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UbahIzinGrupRequest extends FormRequest
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
            'permissions' => [
                'array',
            ],
            'permissions.*' => [
                'string',
                'max:255',
                Rule::exists('uxui_auth_routes', 'url'),
            ],
        ];
    }
}
