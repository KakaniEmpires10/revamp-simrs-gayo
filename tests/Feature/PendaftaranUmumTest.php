<?php

use App\Http\Requests\Pendaftaran\CariPasienRequest;
use App\Http\Requests\Pendaftaran\SimpanPendaftaranUmumRequest;
use App\Http\Requests\Pendaftaran\SimpanPindahRawatInapRequest;
use App\Http\Requests\Pendaftaran\SimpanRujukanInternalRequest;
use App\Http\Requests\Pendaftaran\UbahPendaftaranUmumRequest;
use App\Models\User;
use App\Modules\Bpjs\Http\Requests\VClaimStoreSpriRequest;
use App\Services\Pendaftaran\PendaftaranService;
use Illuminate\Support\Facades\Validator;

test('registration routes require authentication', function (string $method, string $routeName, array $parameters = []) {
    $response = $this->{$method}(route($routeName, $parameters));

    $response->assertRedirect(route('login'));
})->with([
    'daftar pasien page' => ['get', 'pendaftaran.daftar-pasien.index'],
    'data pasien page' => ['get', 'pendaftaran.data-pasien.index'],
    'create data pasien page' => ['get', 'pendaftaran.data-pasien.create'],
    'edit data pasien page' => ['get', 'pendaftaran.data-pasien.edit', ['noRkmMedis' => '000001']],
    'store data pasien endpoint' => ['post', 'pendaftaran.data-pasien.store'],
    'search wilayah endpoint' => ['get', 'pendaftaran.data-pasien.wilayah.search', ['jenis' => 'kelurahan', 'query' => 'l']],
    'update data pasien endpoint' => ['patch', 'pendaftaran.data-pasien.update', ['noRkmMedis' => '000001']],
    'destroy data pasien endpoint' => ['delete', 'pendaftaran.data-pasien.destroy', ['noRkmMedis' => '000001']],
    'merge medical record endpoint' => ['post', 'pendaftaran.data-pasien.gabung-rm.store'],
    'search pasien endpoint' => ['get', 'pendaftaran.daftar-pasien.search'],
    'store registration endpoint' => ['post', 'pendaftaran.daftar-pasien.store'],
    'store internal referral endpoint' => ['post', 'pendaftaran.daftar-pasien.rujuk-internal.store', ['noRawat' => '2026/06/03/000001']],
    'store inpatient transfer endpoint' => ['post', 'pendaftaran.daftar-pasien.pindah-rawat-inap.store', ['noRawat' => '2026/06/03/000001']],
    'store spri endpoint' => ['post', 'integrasi-eksternal.bpjs.vclaim.inpatient-plans.registration.store', ['noRawat' => '2026/06/03/000001']],
    'print queue number endpoint' => ['get', 'pendaftaran.daftar-pasien.cetak.no-antrean-poli', ['noRawat' => '2026/06/03/000001']],
    'print bracelet label endpoint' => ['get', 'pendaftaran.daftar-pasien.cetak.label-gelang', ['noRawat' => '2026/06/03/000001']],
    'print patient bracelet endpoint' => ['get', 'pendaftaran.daftar-pasien.cetak.gelang-pasien', ['noRawat' => '2026/06/03/000001']],
    'update registration endpoint' => ['patch', 'pendaftaran.daftar-pasien.update', ['noRawat' => '2026/06/03/000001']],
    'cancel registration endpoint' => ['patch', 'pendaftaran.daftar-pasien.cancel', ['noRawat' => '2026/06/03/000001']],
    'destroy registration endpoint' => ['delete', 'pendaftaran.daftar-pasien.destroy', ['noRawat' => '2026/06/03/000001']],
]);

test('patient search request validates supported server side search modes', function (): void {
    $request = new CariPasienRequest;

    expect(Validator::make([
        'mode' => 'nik',
        'query' => '1101',
    ], $request->rules())->passes())->toBeTrue();

    expect(Validator::make([
        'mode' => 'nama',
        'query' => 'A',
    ], $request->rules())->passes())->toBeFalse();
});

test('data pasien page validates gender filter values', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);

    $this
        ->actingAs($user)
        ->get(route('pendaftaran.data-pasien.index', ['jk' => 'X']))
        ->assertRedirect()
        ->assertSessionHasErrors('jk');
});

test('destroy data pasien returns feedback from patient service', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);
    $service = Mockery::mock(PendaftaranService::class);

    $service
        ->shouldReceive('hapusDataPasien')
        ->once()
        ->with('000001')
        ->andReturn([
            'berhasil' => false,
            'pesan' => 'Pasien 000001 tidak dapat dihapus karena sudah memiliki riwayat pendaftaran.',
        ]);

    $this->app->instance(PendaftaranService::class, $service);

    $this
        ->actingAs($user)
        ->delete(route('pendaftaran.data-pasien.destroy', ['noRkmMedis' => '000001']))
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'error',
            'message' => 'Pasien 000001 tidak dapat dihapus karena sudah memiliki riwayat pendaftaran.',
        ]);
});

test('wilayah search endpoint returns server side combobox options', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);
    $service = Mockery::mock(PendaftaranService::class);

    $service
        ->shouldReceive('referensiWilayah')
        ->once()
        ->with('kelurahan', 'l')
        ->andReturn([
            [
                'value' => '101',
                'label' => 'Lembah Sabil',
                'description' => 'Kode: 101',
            ],
        ]);

    $this->app->instance(PendaftaranService::class, $service);

    $this
        ->actingAs($user)
        ->get(route('pendaftaran.data-pasien.wilayah.search', [
            'jenis' => 'kelurahan',
            'query' => 'l',
        ]))
        ->assertOk()
        ->assertJson([
            'data' => [
                [
                    'value' => '101',
                    'label' => 'Lembah Sabil',
                    'description' => 'Kode: 101',
                ],
            ],
        ]);
});

test('general registration request validates outpatient and igd payloads', function (): void {
    $request = new SimpanPendaftaranUmumRequest;

    expect(Validator::make([
        'jenis_pendaftaran' => 'rawat_jalan',
        'no_rkm_medis' => '000001',
        'tgl_registrasi' => '2026-06-02',
        'jam_reg' => '09:15',
        'kd_dokter' => 'D0001',
        'kd_poli' => 'U0009',
    ], $request->rules())->passes())->toBeTrue();

    expect(Validator::make([
        'jenis_pendaftaran' => 'igd',
        'no_rkm_medis' => '000001',
        'tgl_registrasi' => '2026-06-02',
        'jam_reg' => '09:15',
        'kd_dokter' => 'D0001',
        'kd_pj' => 'UMU',
        'p_jawab' => 'PENANGGUNG',
        'almt_pj' => 'ALAMAT',
        'hubunganpj' => 'KELUARGA',
        'perujuk' => 'PUSKESMAS',
        'kategori_rujuk' => 'Non-Bedah',
    ], $request->rules())->passes())->toBeTrue();
});

test('edit registration request validates required registration data', function (): void {
    $request = new UbahPendaftaranUmumRequest;

    expect(Validator::make([
        'jenis_pendaftaran' => 'rawat_jalan',
        'tgl_registrasi' => '2026-06-02',
        'jam_reg' => '09:15',
        'kd_dokter' => 'D0001',
        'kd_poli' => 'U0009',
        'kd_pj' => 'UMU',
        'p_jawab' => 'PENANGGUNG',
        'almt_pj' => 'ALAMAT',
        'hubunganpj' => 'KELUARGA',
        'perujuk' => 'PUSKESMAS',
        'kategori_rujuk' => 'Non-Bedah',
    ], $request->rules())->passes())->toBeTrue();

    expect(Validator::make([
        'jenis_pendaftaran' => 'rawat_jalan',
        'tgl_registrasi' => '2026-06-02',
        'jam_reg' => '09:15',
        'kd_dokter' => 'D0001',
    ], $request->rules())->passes())->toBeFalse();
});

test('internal referral request validates target clinic and doctor', function (): void {
    $request = new SimpanRujukanInternalRequest;

    expect(Validator::make([
        'kd_poli' => 'U0009',
        'kd_dokter' => 'D0001',
    ], $request->rules())->passes())->toBeTrue();

    expect(Validator::make([
        'kd_poli' => '',
        'kd_dokter' => '',
    ], $request->rules())->passes())->toBeFalse();
});

test('inpatient transfer request validates room doctor date time and diagnosis', function (): void {
    $request = new SimpanPindahRawatInapRequest;

    expect(Validator::make([
        'kd_kamar' => 'GELIME BED 2',
        'kd_dokter' => 'D0001',
        'tgl_masuk' => '2026-06-05',
        'jam_masuk' => '10:30',
        'diagnosa_awal' => 'A00 - Cholera',
        'kd_penyakit' => 'A00',
        'mode_diagnosa' => 'referensi',
    ], $request->rules())->passes())->toBeTrue();

    expect(Validator::make([
        'kd_kamar' => '',
        'kd_dokter' => '',
        'tgl_masuk' => 'bukan-tanggal',
        'jam_masuk' => '10:30:00',
        'diagnosa_awal' => '',
        'mode_diagnosa' => 'bebas',
    ], $request->rules())->passes())->toBeFalse();
});

test('spri request validates doctor clinic diagnosis and optional sep metadata', function (): void {
    $request = new VClaimStoreSpriRequest;

    expect(Validator::make([
        'kode_dokter' => '12345',
        'nama_dokter' => 'Dokter BPJS',
        'poli_kontrol' => 'INT',
        'nama_poli' => 'Penyakit Dalam',
        'tanggal_kontrol' => '2026-06-05',
        'diagnosa_awal' => 'Nyeri dada',
        'kd_penyakit' => 'I20',
        'mode_diagnosa' => 'referensi',
        'no_sep' => '0301R0010526V000001',
    ], $request->rules())->passes())->toBeTrue();

    expect(Validator::make([
        'kode_dokter' => '',
        'poli_kontrol' => '',
        'tanggal_kontrol' => '05-06-2026',
        'diagnosa_awal' => '',
        'kd_penyakit' => '',
        'mode_diagnosa' => '',
    ], $request->rules())->passes())->toBeFalse();
});

test('store inpatient transfer returns success feedback from registration service', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);
    $service = Mockery::mock(PendaftaranService::class);
    $payload = [
        'kd_kamar' => 'GELIME BED 2',
        'kd_dokter' => 'D0001',
        'tgl_masuk' => '2026-06-05',
        'jam_masuk' => '10:30',
        'diagnosa_awal' => 'A00 - Cholera',
        'kd_penyakit' => 'A00',
        'mode_diagnosa' => 'referensi',
    ];

    $service
        ->shouldReceive('simpanPindahRawatInap')
        ->once()
        ->with('2026/06/03/000001', $payload)
        ->andReturn([
            'berhasil' => true,
            'pesan' => 'Pasien berhasil dipindahkan ke rawat inap.',
        ]);

    $this->app->instance(PendaftaranService::class, $service);

    $this
        ->actingAs($user)
        ->post(route('pendaftaran.daftar-pasien.pindah-rawat-inap.store', ['noRawat' => '2026/06/03/000001']), $payload)
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Pasien berhasil dipindahkan ke rawat inap.',
        ]);
});

test('store inpatient transfer returns error feedback when registration service fails', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);
    $service = Mockery::mock(PendaftaranService::class);
    $payload = [
        'kd_kamar' => 'GELIME BED 2',
        'kd_dokter' => 'D0001',
        'tgl_masuk' => '2026-06-05',
        'jam_masuk' => '10:30',
        'diagnosa_awal' => 'A00 - Cholera',
        'kd_penyakit' => 'A00',
        'mode_diagnosa' => 'referensi',
    ];

    $service
        ->shouldReceive('simpanPindahRawatInap')
        ->once()
        ->with('2026/06/03/000001', $payload)
        ->andReturn([
            'berhasil' => false,
            'pesan' => 'Kamar GELIME BED 2 tidak kosong. Pilih kamar lain.',
        ]);

    $this->app->instance(PendaftaranService::class, $service);

    $this
        ->actingAs($user)
        ->post(route('pendaftaran.daftar-pasien.pindah-rawat-inap.store', ['noRawat' => '2026/06/03/000001']), $payload)
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'error',
            'message' => 'Kamar GELIME BED 2 tidak kosong. Pilih kamar lain.',
        ]);
});

test('store internal referral returns success feedback from registration service', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);
    $service = Mockery::mock(PendaftaranService::class);
    $payload = [
        'kd_poli' => 'U0009',
        'kd_dokter' => 'D0001',
    ];

    $service
        ->shouldReceive('simpanRujukanInternal')
        ->once()
        ->with('2026/06/03/000001', $payload)
        ->andReturn([
            'berhasil' => true,
            'pesan' => 'Rujukan internal berhasil disimpan.',
        ]);

    $this->app->instance(PendaftaranService::class, $service);

    $this
        ->actingAs($user)
        ->post(route('pendaftaran.daftar-pasien.rujuk-internal.store', ['noRawat' => '2026/06/03/000001']), $payload)
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Rujukan internal berhasil disimpan.',
        ]);
});

test('store internal referral returns error feedback when registration service fails', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);
    $service = Mockery::mock(PendaftaranService::class);
    $payload = [
        'kd_poli' => 'U0009',
        'kd_dokter' => 'D0001',
    ];

    $service
        ->shouldReceive('simpanRujukanInternal')
        ->once()
        ->with('2026/06/03/000001', $payload)
        ->andReturn([
            'berhasil' => false,
            'pesan' => 'Rujukan internal ke dokter tersebut sudah pernah dibuat untuk pendaftaran ini.',
        ]);

    $this->app->instance(PendaftaranService::class, $service);

    $this
        ->actingAs($user)
        ->post(route('pendaftaran.daftar-pasien.rujuk-internal.store', ['noRawat' => '2026/06/03/000001']), $payload)
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'error',
            'message' => 'Rujukan internal ke dokter tersebut sudah pernah dibuat untuk pendaftaran ini.',
        ]);
});

test('update registration returns success feedback from registration service', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);
    $service = Mockery::mock(PendaftaranService::class);
    $payload = [
        'jenis_pendaftaran' => 'rawat_jalan',
        'tgl_registrasi' => '2026-06-02',
        'jam_reg' => '09:15',
        'kd_dokter' => 'D0001',
        'kd_poli' => 'U0009',
        'kd_pj' => 'UMU',
        'p_jawab' => 'PENANGGUNG',
        'almt_pj' => 'ALAMAT',
        'hubunganpj' => 'KELUARGA',
        'perujuk' => 'PUSKESMAS',
        'kategori_rujuk' => 'Non-Bedah',
    ];

    $service
        ->shouldReceive('ubah')
        ->once()
        ->with('2026/06/03/000001', $payload)
        ->andReturn([
            'berhasil' => true,
            'pesan' => 'Pendaftaran berhasil diperbarui.',
        ]);

    $this->app->instance(PendaftaranService::class, $service);

    $this
        ->actingAs($user)
        ->patch(route('pendaftaran.daftar-pasien.update', ['noRawat' => '2026/06/03/000001']), $payload)
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Pendaftaran berhasil diperbarui.',
        ]);
});

test('update registration returns error feedback when registration service fails', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);
    $service = Mockery::mock(PendaftaranService::class);
    $payload = [
        'jenis_pendaftaran' => 'rawat_jalan',
        'tgl_registrasi' => '2026-06-02',
        'jam_reg' => '09:15',
        'kd_dokter' => 'D0001',
        'kd_poli' => 'U0009',
        'kd_pj' => 'UMU',
        'p_jawab' => 'PENANGGUNG',
        'almt_pj' => 'ALAMAT',
        'hubunganpj' => 'KELUARGA',
        'perujuk' => 'PUSKESMAS',
        'kategori_rujuk' => 'Non-Bedah',
    ];

    $service
        ->shouldReceive('ubah')
        ->once()
        ->with('2026/06/03/000001', $payload)
        ->andReturn([
            'berhasil' => false,
            'pesan' => 'Pendaftaran 2026/06/03/000001 tidak ditemukan.',
        ]);

    $this->app->instance(PendaftaranService::class, $service);

    $this
        ->actingAs($user)
        ->patch(route('pendaftaran.daftar-pasien.update', ['noRawat' => '2026/06/03/000001']), $payload)
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'error',
            'message' => 'Pendaftaran 2026/06/03/000001 tidak ditemukan.',
        ]);
});

test('destroy registration returns success feedback from deletion service', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);
    $service = Mockery::mock(PendaftaranService::class);

    $service
        ->shouldReceive('hapus')
        ->once()
        ->with('2026/06/03/000001', $user->name)
        ->andReturn([
            'berhasil' => true,
            'pesan' => 'Pendaftaran berhasil dihapus.',
        ]);

    $this->app->instance(PendaftaranService::class, $service);

    $this
        ->actingAs($user)
        ->delete(route('pendaftaran.daftar-pasien.destroy', ['noRawat' => '2026/06/03/000001']))
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Pendaftaran berhasil dihapus.',
        ]);
});

test('destroy registration returns error feedback when deletion service fails', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);
    $service = Mockery::mock(PendaftaranService::class);

    $service
        ->shouldReceive('hapus')
        ->once()
        ->with('2026/06/03/000001', $user->name)
        ->andReturn([
            'berhasil' => false,
            'pesan' => 'Pendaftaran tidak dapat dihapus karena sudah dipakai di pemeriksaan_ralan.',
        ]);

    $this->app->instance(PendaftaranService::class, $service);

    $this
        ->actingAs($user)
        ->delete(route('pendaftaran.daftar-pasien.destroy', ['noRawat' => '2026/06/03/000001']))
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'error',
            'message' => 'Pendaftaran tidak dapat dihapus karena sudah dipakai di pemeriksaan_ralan.',
        ]);
});

test('cancel registration returns success feedback from cancellation service', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);
    $service = Mockery::mock(PendaftaranService::class);

    $service
        ->shouldReceive('batal')
        ->once()
        ->with('2026/06/03/000001', $user->name)
        ->andReturn([
            'berhasil' => true,
            'pesan' => 'Pendaftaran berhasil dibatalkan.',
        ]);

    $this->app->instance(PendaftaranService::class, $service);

    $this
        ->actingAs($user)
        ->patch(route('pendaftaran.daftar-pasien.cancel', ['noRawat' => '2026/06/03/000001']))
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Pendaftaran berhasil dibatalkan.',
        ]);
});

test('cancel registration returns success feedback when canceled registration is reactivated', function (): void {
    $user = User::factory()->make(['id' => 'test-user']);
    $service = Mockery::mock(PendaftaranService::class);

    $service
        ->shouldReceive('batal')
        ->once()
        ->with('2026/06/03/000001', $user->name)
        ->andReturn([
            'berhasil' => true,
            'pesan' => 'Pendaftaran berhasil diaktifkan kembali.',
        ]);

    $this->app->instance(PendaftaranService::class, $service);

    $this
        ->actingAs($user)
        ->patch(route('pendaftaran.daftar-pasien.cancel', ['noRawat' => '2026/06/03/000001']))
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Pendaftaran berhasil diaktifkan kembali.',
        ]);
});
