<?php

use App\Http\Controllers\Settings\FeedbackPreferenceController;
use App\Http\Controllers\Settings\PreferensiIntegrasiController;
use App\Http\Controllers\Settings\PreferensiNavigasiPemeriksaanController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::patch('settings/feedback', [FeedbackPreferenceController::class, 'update'])->name('feedback.update');
    Route::patch('settings/navigasi-pemeriksaan', [PreferensiNavigasiPemeriksaanController::class, 'update'])->name('navigasi-pemeriksaan.update');

    Route::get('settings/integrasi', [PreferensiIntegrasiController::class, 'edit'])->name('integrasi.edit');
    Route::patch('settings/integrasi/bpjs', [PreferensiIntegrasiController::class, 'updateBpjs'])->name('integrasi.bpjs.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});
