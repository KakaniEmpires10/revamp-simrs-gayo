<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UbahNavigasiPemeriksaanRequest;
use App\Models\Settings\UserSetting;
use App\Support\Feedback;
use Illuminate\Http\RedirectResponse;

class PreferensiNavigasiPemeriksaanController extends Controller
{
    public function update(UbahNavigasiPemeriksaanRequest $request): RedirectResponse
    {
        return Feedback::mutasi(
            fn (): mixed => UserSetting::setPemeriksaanNavigationMode(
                $request->user()->getAuthIdentifier(),
                $request->validated('pemeriksaan_navigation_mode'),
            ),
            __('Preferensi navigasi pemeriksaan diperbarui.'),
            __('Preferensi navigasi pemeriksaan gagal diperbarui.'),
        );
    }
}
