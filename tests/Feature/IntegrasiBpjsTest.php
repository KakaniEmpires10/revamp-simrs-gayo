<?php

use App\Http\Requests\Settings\UbahPreferensiIntegrasiBpjsRequest;
use App\Modules\Bpjs\Contracts\BpjsIntegrationSettings;
use App\Modules\Bpjs\Contracts\BpjsTaskIdLogRepository;
use App\Modules\Bpjs\Http\Controllers\BpjsDashboardController;
use App\Modules\Bpjs\Infrastructure\AntrolClient;
use App\Modules\Bpjs\Infrastructure\BpjsHttpClient;
use App\Modules\Bpjs\Infrastructure\VClaimClient;
use App\Modules\Bpjs\Jobs\SendAntrolTaskIdJob;
use App\Modules\Bpjs\Services\AntrolTaskIdService;
use App\Modules\Bpjs\Services\BpjsHealthCheckService;
use App\Modules\Bpjs\Services\VClaimService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use LZCompressor\LZString;

test('antrol task id dispatch is skipped when disabled', function () {
    $repository = Mockery::mock(BpjsTaskIdLogRepository::class);
    $repository
        ->shouldReceive('recordFailure')
        ->once()
        ->with('2026/05/26/000001', 3, 'DISABLED', Mockery::type('string'), Mockery::type(CarbonImmutable::class));

    $service = new AntrolTaskIdService(
        Mockery::mock(AntrolClient::class),
        $repository,
        new class implements BpjsIntegrationSettings
        {
            public function antrolTaskIdEnabled(): bool
            {
                return false;
            }
        },
    );

    $result = $service->dispatch('2026/05/26/000001', 3, CarbonImmutable::parse('2026-05-26 10:00:00'));

    expect($result->status)->toBe('skipped')
        ->and($result->code)->toBe('DISABLED');
});

test('antrol task id dispatch queues a job when enabled', function () {
    Bus::fake();

    $repository = Mockery::mock(BpjsTaskIdLogRepository::class);
    $repository
        ->shouldReceive('hasSuccess')
        ->once()
        ->with('2026/05/26/000001', 4)
        ->andReturnFalse();

    $service = new AntrolTaskIdService(
        Mockery::mock(AntrolClient::class),
        $repository,
        new class implements BpjsIntegrationSettings
        {
            public function antrolTaskIdEnabled(): bool
            {
                return true;
            }
        },
    );

    $result = $service->dispatch('2026/05/26/000001', 4, CarbonImmutable::parse('2026-05-26 10:00:00'));

    expect($result->status)->toBe('queued');

    Bus::assertDispatched(
        SendAntrolTaskIdJob::class,
        fn (SendAntrolTaskIdJob $job): bool => $job->noRawat === '2026/05/26/000001' && $job->taskId === '4',
    );
});

test('antrol task id send now records success response', function () {
    config()->set('bpjs.antrol.base_url', 'https://bpjs.test');
    config()->set('bpjs.antrol.service', 'antreanrs');
    config()->set('bpjs.antrol.cons_id', '123');
    config()->set('bpjs.antrol.secret_key', 'secret');
    config()->set('bpjs.antrol.user_key', 'user-key');
    config()->set('bpjs.antrol.timeout', 10);
    config()->set('bpjs.antrol.connect_timeout', 5);
    config()->set('bpjs.antrol.retry_times', 0);
    config()->set('bpjs.antrol.retry_sleep', 0);

    Http::fake([
        'https://bpjs.test/antreanrs/antrean/updatewaktu' => Http::response([
            'metadata' => [
                'code' => '200',
                'message' => 'Ok',
            ],
        ]),
    ]);

    $repository = Mockery::mock(BpjsTaskIdLogRepository::class);
    $repository->shouldReceive('hasSuccess')->once()->andReturnFalse();
    $repository->shouldReceive('bookingCodeForRawat')->once()->andReturn('BOOK-1');
    $repository
        ->shouldReceive('recordSuccess')
        ->once()
        ->with('2026/05/26/000001', 5, Mockery::type(CarbonImmutable::class));

    $service = new AntrolTaskIdService(
        new AntrolClient(new BpjsHttpClient('antrol')),
        $repository,
        new class implements BpjsIntegrationSettings
        {
            public function antrolTaskIdEnabled(): bool
            {
                return true;
            }
        },
    );

    $result = $service->sendNow('2026/05/26/000001', 5, CarbonImmutable::parse('2026-05-26 10:00:00'));

    expect($result->status)->toBe('success');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://bpjs.test/antreanrs/antrean/updatewaktu'
        && $request['kodebooking'] === 'BOOK-1'
        && $request['taskid'] === '5');
});

test('bpjs submenu groups merge bridge registration menus into vclaim and antrol', function () {
    $controller = new BpjsDashboardController;
    $method = (new ReflectionClass($controller))->getMethod('menuGroups');
    $method->setAccessible(true);

    $groups = $method->invoke($controller);
    $titles = collect($groups)->pluck('title');
    $items = collect($groups)->flatMap(fn (array $group): array => $group['items'])->pluck('title');

    expect($titles->all())->toBe(['VClaim', 'JKN Online / Antrol'])
        ->and($items)->not->toContain('Pembuatan SEP Peserta')
        ->and($items)->not->toContain('Monitoring SKDP / SPRI')
        ->and($items)->not->toContain('Tarik SEP VClaim')
        ->and($items)->toContain('Rencana Kontrol / SKDP')
        ->and($items)->toContain('Rencana Rawat Inap / SPRI')
        ->and($items)->toContain('Antrian dan Kirim Task ID Manual')
        ->and($items)->toContain('Cek Koneksi BPJS')
        ->and($items)->toContain('Cek Rujukan')
        ->and($items)->toContain('Jadwal Dokter HFIS')
        ->and($items->duplicates()->values()->all())->toBe([])
        ->and($items->filter(fn (string $title): bool => $title === 'Jadwal Dokter HFIS')->count())->toBe(1);
});

test('bpjs integration setting request validates boolean toggle', function () {
    $request = new UbahPreferensiIntegrasiBpjsRequest;
    $rules = $request->rules();

    expect(Validator::make(['bpjs_antrol_task_id_enabled' => true], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['bpjs_antrol_task_id_enabled' => false], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['bpjs_antrol_task_id_enabled' => 'aktif'], $rules)->passes())->toBeFalse();
});

test('vclaim service checks connection through signed vclaim endpoint', function () {
    configureVClaimForTest();

    Http::fake([
        'https://bpjs.test/vclaim-rest/referensi/diagnosa/A00' => Http::response([
            'metaData' => [
                'code' => '200',
                'message' => 'OK',
            ],
            'response' => [
                'diagnosa' => [],
            ],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));
    $result = $service->checkConnection();

    expect($result['metadata']['code'])->toBe('200')
        ->and($result['metadata']['message'])->toBe('OK')
        ->and($result['duration_ms'])->toBeGreaterThanOrEqual(1)
        ->and($result['speed']['label'])->toBe('Cepat');
});

test('bpjs health check measures every configured service endpoint', function () {
    configureVClaimForTest();
    config()->set('bpjs.antrol.base_url', 'https://bpjs.test');
    config()->set('bpjs.antrol.service', 'antreanrs');
    config()->set('bpjs.health_checks.fingerprint_url', 'https://fingerprint.test/finger-rest');
    config()->set('bpjs.health_checks.frista_url', 'https://frista.test/frista-api');
    config()->set('bpjs.health_checks.icare_url', 'https://icare.test/wsihs');
    config()->set('bpjs.health_checks.aplicare_url', 'https://aplicare.test/aplicaresws');
    config()->set('bpjs.health_checks.antrean_rs_url', 'https://bpjs.test/antreanrs/antrean/batal');
    config()->set('bpjs.health_checks.vclaim_url', 'https://bpjs.test/vclaim-rest');
    config()->set('bpjs.health_checks.lupis_url', 'https://lupis.test');

    Http::fake([
        '*' => Http::response([
            'metaData' => [
                'code' => '200',
                'message' => 'OK',
            ],
            'response' => [],
        ]),
    ]);

    $result = (new BpjsHealthCheckService)->checkAll();

    expect($result['summary']['total'])->toBe(13)
        ->and($result['summary']['online'])->toBe(13)
        ->and($result['summary']['offline'])->toBe(0)
        ->and($result['services'])->toHaveCount(13)
        ->and($result['services'][0]['duration_ms'])->toBeGreaterThanOrEqual(1)
        ->and(collect($result['services'])->pluck('id')->all())->toContain('cek_koneksi_vclaim', 'cek_koneksi_antreanrs', 'buat_sep');
});

test('bpjs http client does not duplicate service path when base url already includes it', function () {
    configureVClaimForTest();
    config()->set('bpjs.vclaim.base_url', 'https://bpjs.test/vclaim-rest');

    Http::fake([
        'https://bpjs.test/vclaim-rest/referensi/diagnosa/A00' => Http::response([
            'metaData' => [
                'code' => '200',
                'message' => 'OK',
            ],
            'response' => [
                'diagnosa' => [],
            ],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));

    expect($service->checkConnection()['metadata']['code'])->toBe('200');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://bpjs.test/vclaim-rest/referensi/diagnosa/A00'
        && $request->hasHeader('Content-Type', 'Application/json')
        && $request->hasHeader('Accept', 'application/json'));
});

test('vclaim service maps monitoring visit rows from bpjs response', function () {
    configureVClaimForTest();

    Http::fake([
        'https://bpjs.test/vclaim-rest/Monitoring/Kunjungan/Tanggal/2026-05-26/JnsPelayanan/2' => Http::response([
            'metaData' => [
                'code' => '200',
                'message' => 'OK',
            ],
            'response' => [
                'sep' => [
                    ['noSep' => '0301R0010526V000001', 'nama' => 'Pasien Test'],
                ],
            ],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));
    $result = $service->monitoringVisits('2026-05-26', '2');

    expect($result['metadata']['code'])->toBe('200')
        ->and($result['rows'])->toHaveCount(1)
        ->and($result['rows'][0]['noSep'])->toBe('0301R0010526V000001');
});

test('vclaim service surfaces non json bpjs mapping response as metadata message', function () {
    configureVClaimForTest();

    Http::fake([
        'https://bpjs.test/vclaim-rest/Monitoring/Kunjungan/Tanggal/2026-05-26/JnsPelayanan/2' => Http::response('No Mapping Rule matched', 200),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));
    $result = $service->monitoringVisits('2026-05-26', '2');

    expect($result['metadata']['code'])->toBe('200')
        ->and($result['metadata']['message'])->toBe('No Mapping Rule matched')
        ->and($result['rows'])->toBe([]);
});

test('vclaim service surfaces non json bpjs error status as metadata message', function () {
    configureVClaimForTest();

    Http::fake([
        'https://bpjs.test/vclaim-rest/Monitoring/Kunjungan/Tanggal/2026-05-26/JnsPelayanan/2' => Http::response('No Mapping Rule matched', 404),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));
    $result = $service->monitoringVisits('2026-05-26', '2');

    expect($result['metadata']['code'])->toBe('404')
        ->and($result['metadata']['message'])->toBe('No Mapping Rule matched')
        ->and($result['rows'])->toBe([]);
});

test('vclaim service decrypts and decompresses encrypted bpjs monitoring response', function () {
    configureVClaimForTest();

    Http::fake([
        'https://bpjs.test/vclaim-rest/Monitoring/Kunjungan/Tanggal/2026-05-26/JnsPelayanan/2' => function ($request) {
            $decryptKey = $request->header('X-cons-id')[0]
                .config('bpjs.vclaim.secret_key')
                .$request->header('X-timestamp')[0];

            return Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => encryptBpjsResponse($decryptKey, [
                    'sep' => [
                        ['noSep' => '0301R0010526V000002', 'nama' => 'Pasien Encrypt'],
                    ],
                ]),
            ]);
        },
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));
    $result = $service->monitoringVisits('2026-05-26', '2');

    expect($result['metadata']['code'])->toBe('200')
        ->and($result['rows'][0]['nama'])->toBe('Pasien Encrypt');
});

test('vclaim service maps participant and outbound referrals', function () {
    configureVClaimForTest();

    Http::fake([
        'https://bpjs.test/vclaim-rest/Rujukan/List/Peserta/0001234567890' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'rujukan' => [
                    ['noKunjungan' => '0123B0000526Y000001'],
                ],
            ],
        ]),
        'https://bpjs.test/vclaim-rest/Rujukan/Keluar/List/tglMulai/2026-05-26/tglAkhir/2026-05-26' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'list' => [
                    ['noRujukan' => '0301R0010526B000001'],
                ],
            ],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));

    expect($service->referralsForParticipant('0001234567890', 'pcare')['rows'][0]['noKunjungan'])->toBe('0123B0000526Y000001')
        ->and($service->outboundReferrals('2026-05-26', '2026-05-26')['rows'][0]['noRujukan'])->toBe('0301R0010526B000001');
});

test('vclaim service updates and deletes outbound referral through fake bpjs endpoint only', function () {
    configureVClaimForTest();
    Http::preventStrayRequests();

    Http::fake([
        'https://bpjs.test/vclaim-rest/Rujukan/Keluar/0301R0010526B000001' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'Sukses'],
            'response' => [
                'rujukan' => [
                    'noRujukan' => '0301R0010526B000001',
                    'noSep' => '0301R0010526V000001',
                    'nama' => 'Pasien Test',
                ],
            ],
        ]),
        'https://bpjs.test/vclaim-rest/Rujukan/2.0/Update' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'Rujukan berhasil diubah'],
            'response' => [
                'noRujukan' => '0301R0010526B000001',
            ],
        ]),
        'https://bpjs.test/vclaim-rest/Rujukan/delete' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'Rujukan berhasil dihapus'],
            'response' => [],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));

    $detail = $service->outboundReferral('0301R0010526B000001');
    $update = $service->updateOutboundReferral('0301R0010526B000001', [
        'tanggal_rujukan' => '2026-05-26',
        'tanggal_rencana_kunjungan' => '2026-05-29',
        'ppk_dirujuk' => '0301R001',
        'jenis_pelayanan' => '2',
        'catatan' => 'Kontrol lanjutan',
        'diagnosa_rujukan' => 'A00',
        'tipe_rujukan' => '0',
        'poli_rujukan' => 'INT',
    ], 'Admin SIMRS');
    $delete = $service->deleteOutboundReferral('0301R0010526B000001', 'Admin SIMRS');

    expect($detail['metadata']['code'])->toBe('200')
        ->and($detail['rujukan']['noRujukan'])->toBe('0301R0010526B000001')
        ->and($detail['rujukan']['noSep'])->toBe('0301R0010526V000001')
        ->and($update['metadata']['code'])->toBe('200')
        ->and($update['rujukan']['noRujukan'])->toBe('0301R0010526B000001')
        ->and($delete['metadata']['code'])->toBe('200');

    Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
        && $request->url() === 'https://bpjs.test/vclaim-rest/Rujukan/2.0/Update'
        && $request['request']['t_rujukan']['noRujukan'] === '0301R0010526B000001'
        && $request['request']['t_rujukan']['tglRujukan'] === '2026-05-26'
        && $request['request']['t_rujukan']['tglRencanaKunjungan'] === '2026-05-29'
        && $request['request']['t_rujukan']['ppkDirujuk'] === '0301R001'
        && $request['request']['t_rujukan']['jnsPelayanan'] === '2'
        && $request['request']['t_rujukan']['catatan'] === 'Kontrol lanjutan'
        && $request['request']['t_rujukan']['diagRujukan'] === 'A00'
        && $request['request']['t_rujukan']['tipeRujukan'] === '0'
        && $request['request']['t_rujukan']['poliRujukan'] === 'INT'
        && $request['request']['t_rujukan']['user'] === 'Admin SIMRS');

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://bpjs.test/vclaim-rest/Rujukan/delete'
        && $request['request']['t_rujukan']['noRujukan'] === '0301R0010526B000001'
        && $request['request']['t_rujukan']['user'] === 'Admin SIMRS');
});

test('vclaim service maps service history prb approvals fingerprints and claims', function () {
    configureVClaimForTest();
    Http::preventStrayRequests();

    Http::fake([
        'https://bpjs.test/vclaim-rest/monitoring/HistoriPelayanan/NoKartu/0001234567890/tglMulai/2026-05-01/tglAkhir/2026-05-26' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'histori' => [
                    ['noSep' => '0301R0010526V000001'],
                ],
            ],
        ]),
        'https://bpjs.test/vclaim-rest/prb/tglMulai/2026-05-01/tglAkhir/2026-05-26' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'prb' => [
                    'list' => [
                        ['noSRB' => 'SRB-001'],
                    ],
                ],
            ],
        ]),
        'https://bpjs.test/vclaim-rest/SEP/persetujuanSEP/list/bulan/5/tahun/2026' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'list' => [
                    ['noKartu' => '0001234567890'],
                ],
            ],
        ]),
        'https://bpjs.test/vclaim-rest/SEP/FingerPrint/List/Peserta/TglPelayanan/2026-05-26' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'list' => [
                    ['noKartu' => '0001234567890', 'noSEP' => '0301R0010526V000001', 'status' => '1'],
                    ['noKartu' => '0000000000000', 'noSEP' => null, 'status' => '0'],
                ],
            ],
        ]),
        'https://bpjs.test/vclaim-rest/Monitoring/Klaim/Tanggal/2026-05-26/JnsPelayanan/1/Status/1' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'klaim' => [
                    ['noSEP' => '0301R0010526V000001'],
                ],
            ],
        ]),
    ]);

    mockFingerprintPatients([
        (object) [
            'no_peserta' => '0001234567890',
            'no_rkm_medis' => '000001',
            'nm_pasien' => 'Pasien Finger',
            'jk' => 'P',
        ],
    ], ['0001234567890', '0000000000000']);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));
    $fingerprints = $service->fingerprints('2026-05-26');

    expect($service->serviceHistory('0001234567890', '2026-05-01', '2026-05-26')['rows'][0]['noSep'])->toBe('0301R0010526V000001')
        ->and($service->prbData('2026-05-01', '2026-05-26')['rows'][0]['noSRB'])->toBe('SRB-001')
        ->and($service->sepApprovals('5', '2026')['rows'][0]['noKartu'])->toBe('0001234567890')
        ->and($fingerprints['rows'][0]['status'])->toBe('1')
        ->and($fingerprints['rows'][0]['pasien']['no_rkm_medis'])->toBe('000001')
        ->and($fingerprints['rows'][0]['pasien']['nm_pasien'])->toBe('Pasien Finger')
        ->and($fingerprints['rows'][0]['pasien']['jk'])->toBe('P')
        ->and($fingerprints['rows'][1]['pasien'])->toBe([
            'no_rkm_medis' => null,
            'nm_pasien' => null,
            'jk' => null,
        ])
        ->and($service->claimMonitoring('2026-05-26', '1', '1')['rows'][0]['noSEP'])->toBe('0301R0010526V000001');
});

test('vclaim service filters control plan list into skdp and spri rows', function () {
    configureVClaimForTest();

    Http::fake([
        'https://bpjs.test/vclaim-rest/RencanaKontrol/ListRencanaKontrol/tglAwal/2026-05-26/tglAkhir/2026-05-26/filter/1' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'list' => [
                    ['noSuratKontrol' => '0301R0010526K000001', 'jnsKontrol' => '2'],
                    ['noSuratKontrol' => '0301R0010526S000001', 'jnsKontrol' => '1'],
                ],
            ],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));

    expect($service->controlPlans('2026-05-26', '2026-05-26', '1', 'skdp')['rows'])
        ->toHaveCount(1)
        ->and($service->controlPlans('2026-05-26', '2026-05-26', '1', 'spri')['rows'])
        ->toHaveCount(1);
});

test('vclaim service loads skdp specialist and doctor references through fake bpjs endpoint only', function () {
    configureVClaimForTest();
    Http::preventStrayRequests();

    Http::fake([
        'https://bpjs.test/vclaim-rest/RencanaKontrol/noSuratKontrol/0301R0010526K000001' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'noSuratKontrol' => '0301R0010526K000001',
                'tglRencanaKontrol' => '2026-05-29',
                'poliTujuan' => 'INT',
                'namaPoliTujuan' => 'PENYAKIT DALAM',
                'kodeDokter' => '10529',
                'namaDokter' => 'dr. Lindawati, Sp.PD',
                'sep' => [
                    'noSep' => '0301R0010526V000001',
                    'peserta' => [
                        'noKartu' => '0001234567890',
                        'nama' => 'PASIEN TEST',
                    ],
                ],
            ],
        ]),
        'https://bpjs.test/vclaim-rest/RencanaKontrol/ListSpesialistik/JnsKontrol/2/nomor/0301R0010526V000001/TglRencanaKontrol/2026-05-29' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'list' => [
                    ['kodePoli' => 'INT', 'namaPoli' => 'PENYAKIT DALAM'],
                ],
            ],
        ]),
        'https://bpjs.test/vclaim-rest/RencanaKontrol/JadwalPraktekDokter/JnsKontrol/2/KdPoli/INT/TglRencanaKontrol/2026-05-29' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'list' => [
                    ['kodeDokter' => '10529', 'namaDokter' => 'dr. Lindawati, Sp.PD'],
                ],
            ],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));

    $detail = $service->controlPlan('0301R0010526K000001');
    $specialists = $service->controlPlanSpecialists('0301R0010526V000001', '2026-05-29');
    $doctors = $service->controlPlanDoctors('INT', '2026-05-29');

    expect($detail['kontrol']['noSuratKontrol'])->toBe('0301R0010526K000001')
        ->and($detail['kontrol']['sep']['noSep'])->toBe('0301R0010526V000001')
        ->and($specialists['rows'][0]['kodePoli'])->toBe('INT')
        ->and($doctors['rows'][0]['kodeDokter'])->toBe('10529');

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/noSuratKontrol/0301R0010526K000001');

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/ListSpesialistik/JnsKontrol/2/nomor/0301R0010526V000001/TglRencanaKontrol/2026-05-29');

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/JadwalPraktekDokter/JnsKontrol/2/KdPoli/INT/TglRencanaKontrol/2026-05-29');
});

test('vclaim service creates skdp and spri through legacy rencana kontrol endpoints', function () {
    configureVClaimForTest();

    Http::fake([
        'https://bpjs.test/vclaim-rest/RencanaKontrol/insert' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'noSuratKontrol' => '0301R0010526K000001',
            ],
        ]),
        'https://bpjs.test/vclaim-rest/RencanaKontrol/InsertSPRI' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'noSPRI' => '0301R0010526S000001',
            ],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));

    $skdp = $service->createControlPlan([
        'no_sep' => '0301R0010526V000001',
        'kode_dokter' => '12345',
        'poli_kontrol' => 'INT',
        'tanggal_kontrol' => '2026-05-27',
    ], 'Admin SIMRS');

    $spri = $service->createInpatientPlan([
        'no_kartu' => '0001234567890',
        'kode_dokter' => '54321',
        'poli_kontrol' => 'INT',
        'tanggal_kontrol' => '2026-05-28',
    ], 'Admin SIMRS');

    expect($skdp['skdp']['noSuratKontrol'])->toBe('0301R0010526K000001')
        ->and($spri['spri']['noSPRI'])->toBe('0301R0010526S000001');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/insert'
        && $request['request']['noSEP'] === '0301R0010526V000001'
        && $request['request']['kodeDokter'] === '12345'
        && $request['request']['poliKontrol'] === 'INT'
        && $request['request']['tglRencanaKontrol'] === '2026-05-27'
        && $request['request']['user'] === 'Admin SIMRS');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/InsertSPRI'
        && $request['request']['noKartu'] === '0001234567890'
        && $request['request']['kodeDokter'] === '54321'
        && $request['request']['poliKontrol'] === 'INT'
        && $request['request']['tglRencanaKontrol'] === '2026-05-28'
        && $request['request']['user'] === 'Admin SIMRS');
});

test('vclaim service can fetch sep for local pull without real bpjs request', function () {
    configureVClaimForTest();
    Http::preventStrayRequests();

    Http::fake([
        'https://bpjs.test/vclaim-rest/SEP/0301R0010526V000001' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'sep' => [
                    'noSep' => '0301R0010526V000001',
                    'jnsPelayanan' => 'Rawat Jalan',
                ],
            ],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));
    $result = $service->sep('0301R0010526V000001');

    expect($result['metadata']['code'])->toBe('200')
        ->and($result['sep']['noSep'])->toBe('0301R0010526V000001');
});

test('vclaim service sends delete sep payload to fake bpjs endpoint only', function () {
    configureVClaimForTest();
    Http::preventStrayRequests();

    Http::fake([
        'https://bpjs.test/vclaim-rest/SEP/2.0/delete' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'SEP berhasil dihapus'],
            'response' => [],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));
    $result = $service->deleteSep('0301R0010526V000001', 'Admin SIMRS');

    expect($result['metadata']['code'])->toBe('200');

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://bpjs.test/vclaim-rest/SEP/2.0/delete'
        && $request['request']['t_sep']['noSep'] === '0301R0010526V000001'
        && $request['request']['t_sep']['user'] === 'Admin SIMRS');
});

test('vclaim service updates and deletes skdp through fake bpjs endpoint only', function () {
    configureVClaimForTest();
    Http::preventStrayRequests();

    Http::fake([
        'https://bpjs.test/vclaim-rest/RencanaKontrol/Update' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'SKDP berhasil diubah'],
            'response' => [
                'noSuratKontrol' => '0301R0010526K000001',
                'tglRencanaKontrol' => '2026-05-29',
                'namaDokter' => 'Dokter BPJS',
            ],
        ]),
        'https://bpjs.test/vclaim-rest/RencanaKontrol/Delete' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'SKDP berhasil dihapus'],
            'response' => [],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));

    $update = $service->updateControlPlan('0301R0010526K000001', [
        'no_sep' => '0301R0010526V000001',
        'kode_dokter' => '12345',
        'poli_kontrol' => 'INT',
        'tanggal_kontrol' => '2026-05-29',
    ], 'Admin SIMRS');
    $delete = $service->deleteControlPlan('0301R0010526K000001', 'Admin SIMRS');

    expect($update['metadata']['code'])->toBe('200')
        ->and($update['skdp']['noSuratKontrol'])->toBe('0301R0010526K000001')
        ->and($delete['metadata']['code'])->toBe('200');

    Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
        && $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/Update'
        && $request['request']['noSuratKontrol'] === '0301R0010526K000001'
        && $request['request']['noSEP'] === '0301R0010526V000001'
        && $request['request']['kodeDokter'] === '12345'
        && $request['request']['poliKontrol'] === 'INT'
        && $request['request']['tglRencanaKontrol'] === '2026-05-29'
        && $request['request']['user'] === 'Admin SIMRS');

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/Delete'
        && $request['request']['t_suratkontrol']['noSuratKontrol'] === '0301R0010526K000001'
        && $request['request']['t_suratkontrol']['user'] === 'Admin SIMRS');
});

test('vclaim service updates and deletes spri through fake bpjs endpoint only', function () {
    configureVClaimForTest();
    Http::preventStrayRequests();

    Http::fake([
        'https://bpjs.test/vclaim-rest/RencanaKontrol/UpdateSPRI' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'SPRI berhasil diubah'],
            'response' => [
                'noSPRI' => '0301R0010526S000001',
                'tglRencanaKontrol' => '2026-05-30',
                'namaDokter' => 'Dokter Rawat Inap',
            ],
        ]),
        'https://bpjs.test/vclaim-rest/RencanaKontrol/Delete' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'SPRI berhasil dihapus'],
            'response' => [],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));

    $update = $service->updateInpatientPlan('0301R0010526S000001', [
        'kode_dokter' => '54321',
        'poli_kontrol' => 'BED',
        'tanggal_kontrol' => '2026-05-30',
    ], 'Admin SIMRS');
    $delete = $service->deleteControlPlan('0301R0010526S000001', 'Admin SIMRS');

    expect($update['metadata']['code'])->toBe('200')
        ->and($update['spri']['noSPRI'])->toBe('0301R0010526S000001')
        ->and($delete['metadata']['code'])->toBe('200');

    Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
        && $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/UpdateSPRI'
        && $request['request']['noSPRI'] === '0301R0010526S000001'
        && $request['request']['kodeDokter'] === '54321'
        && $request['request']['poliKontrol'] === 'BED'
        && $request['request']['tglRencanaKontrol'] === '2026-05-30'
        && $request['request']['user'] === 'Admin SIMRS');

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/Delete'
        && $request['request']['t_suratkontrol']['noSuratKontrol'] === '0301R0010526S000001'
        && $request['request']['t_suratkontrol']['user'] === 'Admin SIMRS');
});

test('vclaim service can resolve participant by nik before checking referrals', function () {
    configureVClaimForTest();

    Http::fake([
        'https://bpjs.test/vclaim-rest/Peserta/nik/1101012605260001/tglSEP/2026-05-26' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'peserta' => [
                    'noKartu' => '0001234567890',
                    'nama' => 'Pasien NIK',
                    'nik' => '1101012605260001',
                ],
            ],
        ]),
        'https://bpjs.test/vclaim-rest/Rujukan/RS/List/Peserta/0001234567890' => Http::response([
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'rujukan' => [
                    ['noKunjungan' => '0123R0000526Y000001'],
                ],
            ],
        ]),
    ]);

    $service = new VClaimService(new VClaimClient(new BpjsHttpClient('vclaim')));
    $result = $service->referralsForIdentifier('nik', '1101012605260001', 'rs', '2026-05-26');

    expect($result['peserta']['noKartu'])->toBe('0001234567890')
        ->and($result['rows'][0]['noKunjungan'])->toBe('0123R0000526Y000001');
});

function configureVClaimForTest(): void
{
    config()->set('bpjs.vclaim.base_url', 'https://bpjs.test');
    config()->set('bpjs.vclaim.service', 'vclaim-rest');
    config()->set('bpjs.vclaim.cons_id', '123');
    config()->set('bpjs.vclaim.secret_key', 'secret');
    config()->set('bpjs.vclaim.user_key', 'user-key');
    config()->set('bpjs.vclaim.timeout', 10);
    config()->set('bpjs.vclaim.connect_timeout', 5);
    config()->set('bpjs.vclaim.retry_times', 0);
    config()->set('bpjs.vclaim.retry_sleep', 0);
    config()->set('bpjs.vclaim.verify_ssl', false);
}

/**
 * @param  array<int, object>  $patients
 * @param  array<int, string>  $cardNumbers
 */
function mockFingerprintPatients(array $patients, array $cardNumbers): void
{
    $query = Mockery::mock();

    DB::shouldReceive('table')
        ->once()
        ->with('pasien')
        ->andReturn($query);

    $query->shouldReceive('select')
        ->once()
        ->with('no_peserta', 'no_rkm_medis', 'nm_pasien', 'jk')
        ->andReturnSelf();

    $query->shouldReceive('whereIn')
        ->once()
        ->with('no_peserta', $cardNumbers)
        ->andReturnSelf();

    $query->shouldReceive('get')
        ->once()
        ->andReturn(collect($patients));
}

/**
 * @param  array<string, mixed>  $payload
 */
function encryptBpjsResponse(string $decryptKey, array $payload): string
{
    $keyHash = hex2bin(hash('sha256', $decryptKey));
    $iv = substr(hex2bin(hash('sha256', $decryptKey)), 0, 16);
    $compressed = LZString::compressToEncodedURIComponent(json_encode($payload, JSON_THROW_ON_ERROR));

    return base64_encode(openssl_encrypt($compressed, 'AES-256-CBC', $keyHash, OPENSSL_RAW_DATA, $iv));
}
