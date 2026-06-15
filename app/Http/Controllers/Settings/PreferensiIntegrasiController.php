<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UbahPreferensiIntegrasiBpjsRequest;
use App\Models\Settings\AppSetting;
use App\Support\Feedback;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PreferensiIntegrasiController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/Integrasi', [
            'bpjsAntrolTaskIdEnabled' => AppSetting::bpjsAntrolTaskIdEnabled(),
        ]);
    }

    public function updateBpjs(UbahPreferensiIntegrasiBpjsRequest $request): RedirectResponse
    {
        return Feedback::mutasi(
            fn (): mixed => AppSetting::setBpjsAntrolTaskIdEnabled($request->boolean('bpjs_antrol_task_id_enabled')),
            __('Pengaturan integrasi BPJS diperbarui.'),
            __('Pengaturan integrasi BPJS gagal diperbarui.'),
        );
    }
}
