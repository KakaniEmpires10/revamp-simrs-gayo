<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UbahNavigasiPemeriksaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'pemeriksaan_navigation_mode' => ['required', 'string', Rule::in(['sidebar-tree', 'top-tab'])],
        ];
    }
}
