<?php

use App\Support\Feedback;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

test('mutation feedback returns error toast and validation errors', function (): void {
    Route::middleware('web')->post('/_test-feedback-mutasi-validation', fn () => Feedback::mutasi(
        fn () => throw ValidationException::withMessages(['nama' => 'Nama wajib diisi.']),
        'Data berhasil disimpan.',
        'Data gagal disimpan.',
    ));

    $this
        ->post('/_test-feedback-mutasi-validation')
        ->assertRedirect()
        ->assertSessionHasErrors(['nama'])
        ->assertSessionHas('toast', [
            'type' => 'error',
            'message' => 'Data gagal disimpan. Nama wajib diisi.',
        ]);
});

test('result feedback preserves service error message', function (): void {
    Route::middleware('web')->delete('/_test-feedback-hasil-error', fn () => Feedback::hasil(
        fn (): array => [
            'berhasil' => false,
            'pesan' => 'Pendaftaran tidak dapat dihapus karena sudah dipakai di cppt.',
        ],
        'Pendaftaran gagal dihapus.',
    ));

    $this
        ->delete('/_test-feedback-hasil-error')
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'error',
            'message' => 'Pendaftaran tidak dapat dihapus karena sudah dipakai di cppt.',
        ]);
});

test('metadata feedback converts non success metadata into error toast', function (): void {
    Route::middleware('web')->post('/_test-feedback-metadata-error', fn () => Feedback::metadata(
        fn (): array => [
            'metadata' => [
                'code' => '201',
                'message' => 'Data BPJS tidak valid.',
            ],
        ],
        'Data BPJS gagal diproses.',
    ));

    $this
        ->post('/_test-feedback-metadata-error')
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'error',
            'message' => 'Data BPJS tidak valid.',
        ]);
});
