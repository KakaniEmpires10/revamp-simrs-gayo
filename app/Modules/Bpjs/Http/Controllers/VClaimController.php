<?php

namespace App\Modules\Bpjs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bpjs\Http\Requests\VClaimBulanTahunRequest;
use App\Modules\Bpjs\Http\Requests\VClaimCekRujukanRequest;
use App\Modules\Bpjs\Http\Requests\VClaimDataFingerprintRequest;
use App\Modules\Bpjs\Http\Requests\VClaimDataRujukanRequest;
use App\Modules\Bpjs\Http\Requests\VClaimListRencanaKontrolRequest;
use App\Modules\Bpjs\Http\Requests\VClaimMonitoringKlaimRequest;
use App\Modules\Bpjs\Http\Requests\VClaimMonitoringKunjunganRequest;
use App\Modules\Bpjs\Http\Requests\VClaimRentangTanggalRequest;
use App\Modules\Bpjs\Http\Requests\VClaimStoreRencanaKontrolRequest;
use App\Modules\Bpjs\Http\Requests\VClaimStoreRencanaRawatInapRequest;
use App\Modules\Bpjs\Http\Requests\VClaimStoreRujukanKeluarRequest;
use App\Modules\Bpjs\Http\Requests\VClaimStoreSpriRequest;
use App\Modules\Bpjs\Http\Requests\VClaimTarikSepRequest;
use App\Modules\Bpjs\Http\Requests\VClaimUpdateRencanaKontrolRequest;
use App\Modules\Bpjs\Http\Requests\VClaimUpdateRencanaRawatInapRequest;
use App\Modules\Bpjs\Http\Requests\VClaimUpdateRujukanKeluarRequest;
use App\Modules\Bpjs\Services\BpjsHealthCheckService;
use App\Modules\Bpjs\Services\VClaimService;
use App\Support\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VClaimController extends Controller
{
    public function connection(Request $request, BpjsHealthCheckService $service): Response
    {
        return Inertia::render('IntegrasiEksternal/Bpjs/VClaim/CekKoneksi', [
            'result' => $request->boolean('check') ? $service->checkAll() : null,
            'filters' => [
                'check' => $request->boolean('check'),
            ],
        ]);
    }

    public function monitoringVisits(VClaimMonitoringKunjunganRequest $request, VClaimService $service): Response
    {
        $filters = [
            'date' => $request->validated('date') ?: today()->toDateString(),
            'service_type' => $request->validated('service_type') ?: '2',
        ];

        return Inertia::render('IntegrasiEksternal/Bpjs/VClaim/MonitoringKunjungan', [
            'result' => $service->monitoringVisits($filters['date'], $filters['service_type']),
            'filters' => $filters,
        ]);
    }

    public function referralCheck(VClaimCekRujukanRequest $request, VClaimService $service): Response
    {
        $filters = [
            'identifier_type' => $request->validated('identifier_type') ?: 'card',
            'identifier' => $request->validated('identifier') ?: '',
            'source' => $request->validated('source') ?: 'pcare',
        ];

        return Inertia::render('IntegrasiEksternal/Bpjs/VClaim/CekRujukan', [
            'result' => filled($filters['identifier'])
                ? $service->referralsForIdentifier($filters['identifier_type'], $filters['identifier'], $filters['source'], today()->toDateString())
                : null,
            'filters' => $filters,
        ]);
    }

    public function outboundReferrals(VClaimDataRujukanRequest $request, VClaimService $service): Response
    {
        $filters = [
            'start_date' => $request->validated('start_date') ?: today()->toDateString(),
            'end_date' => $request->validated('end_date') ?: today()->toDateString(),
        ];

        return Inertia::render('IntegrasiEksternal/Bpjs/VClaim/DataRujukan', [
            'result' => $service->outboundReferrals($filters['start_date'], $filters['end_date']),
            'filters' => $filters,
        ]);
    }

    public function storeOutboundReferral(VClaimStoreRujukanKeluarRequest $request, VClaimService $service): RedirectResponse
    {
        return Feedback::metadata(function () use ($request, $service): array {
            $result = $service->createOutboundReferral(
                $request->validated(),
                $this->currentUser($request),
            );

            if ($result['metadata']['code'] === '200') {
                $this->storeLocalOutboundReferral($request->validated(), $this->currentUser($request), $result['rujukan']);
            }

            return $result;
        }, 'Rujukan keluar gagal disimpan.');
    }

    public function outboundReferralDetail(VClaimService $service, string $noRujukan): JsonResponse
    {
        return response()->json($service->outboundReferral($noRujukan));
    }

    public function updateOutboundReferral(VClaimUpdateRujukanKeluarRequest $request, VClaimService $service, string $noRujukan): RedirectResponse
    {
        return Feedback::metadata(function () use ($request, $service, $noRujukan): array {
            $result = $service->updateOutboundReferral(
                $noRujukan,
                $request->validated(),
                $this->currentUser($request),
            );

            if ($result['metadata']['code'] === '200') {
                $this->updateLocalOutboundReferral($noRujukan, $request->validated(), $this->currentUser($request));
            }

            return $result;
        }, 'Rujukan keluar gagal diperbarui.');
    }

    public function destroyOutboundReferral(Request $request, VClaimService $service, string $noRujukan): RedirectResponse
    {
        return Feedback::metadata(function () use ($request, $service, $noRujukan): array {
            $result = $service->deleteOutboundReferral($noRujukan, $this->currentUser($request));

            if ($result['metadata']['code'] === '200') {
                DB::table('bridging_rujukan_bpjs')->where('no_rujukan', $noRujukan)->delete();
            }

            return $result;
        }, 'Rujukan keluar gagal dihapus.');
    }

    public function prbData(VClaimRentangTanggalRequest $request, VClaimService $service): Response
    {
        $filters = [
            'start_date' => $request->validated('start_date') ?: today()->toDateString(),
            'end_date' => $request->validated('end_date') ?: today()->toDateString(),
        ];

        return Inertia::render('IntegrasiEksternal/Bpjs/VClaim/DataPrb', [
            'result' => $service->prbData($filters['start_date'], $filters['end_date']),
            'filters' => $filters,
        ]);
    }

    public function sepApprovals(VClaimBulanTahunRequest $request, VClaimService $service): Response
    {
        $filters = [
            'month' => (string) ($request->validated('month') ?: today()->month),
            'year' => (string) ($request->validated('year') ?: today()->year),
        ];

        return Inertia::render('IntegrasiEksternal/Bpjs/VClaim/PersetujuanSep', [
            'result' => $service->sepApprovals($filters['month'], $filters['year']),
            'filters' => $filters,
        ]);
    }

    public function fingerprints(VClaimDataFingerprintRequest $request, VClaimService $service): Response
    {
        $filters = [
            'date' => $request->validated('date') ?: today()->toDateString(),
        ];

        return Inertia::render('IntegrasiEksternal/Bpjs/VClaim/DataFingerprint', [
            'result' => $service->fingerprints($filters['date']),
            'filters' => $filters,
        ]);
    }

    public function claimMonitoring(VClaimMonitoringKlaimRequest $request, VClaimService $service): Response
    {
        $filters = [
            'date' => $request->validated('date') ?: today()->toDateString(),
            'service_type' => $request->validated('service_type') ?: '1',
            'status' => $request->validated('status') ?: '1',
        ];

        return Inertia::render('IntegrasiEksternal/Bpjs/VClaim/MonitoringKlaim', [
            'result' => $service->claimMonitoring($filters['date'], $filters['service_type'], $filters['status']),
            'filters' => $filters,
        ]);
    }

    public function controlPlans(VClaimListRencanaKontrolRequest $request, VClaimService $service): Response
    {
        $filters = $this->controlPlanFilters($request);

        return Inertia::render('IntegrasiEksternal/Bpjs/VClaim/RencanaKontrol', [
            'result' => $service->controlPlans($filters['start_date'], $filters['end_date'], $filters['filter'], 'skdp'),
            'filters' => $filters,
        ]);
    }

    public function storeControlPlan(VClaimStoreRencanaKontrolRequest $request, VClaimService $service): RedirectResponse
    {
        return Feedback::metadata(function () use ($request, $service): array {
            $result = $service->createControlPlan(
                $request->validated(),
                $this->currentUser($request),
            );

            if ($result['metadata']['code'] === '200') {
                $this->storeLocalControlPlan($request->validated(), $result['skdp']);
            }

            return $result;
        }, 'Rencana kontrol gagal disimpan.');
    }

    public function controlPlanBySepDetail(VClaimService $service, string $noSep): JsonResponse
    {
        return response()->json($service->controlPlanBySep($noSep));
    }

    public function controlPlanDetail(VClaimService $service, string $noSurat): JsonResponse
    {
        return response()->json($service->controlPlan($noSurat));
    }

    public function inpatientPlanDetail(VClaimService $service, string $noSurat): JsonResponse
    {
        return response()->json($service->controlPlan($noSurat));
    }

    public function controlPlanSpecialists(Request $request, VClaimService $service): JsonResponse
    {
        $data = $request->validate([
            'no_sep' => ['required', 'string', 'max:40'],
            'tanggal_kontrol' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json($service->controlPlanSpecialists($data['no_sep'], $data['tanggal_kontrol']));
    }

    public function controlPlanDoctors(Request $request, VClaimService $service): JsonResponse
    {
        $data = $request->validate([
            'poli_kontrol' => ['required', 'string', 'max:20'],
            'tanggal_kontrol' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json($service->controlPlanDoctors($data['poli_kontrol'], $data['tanggal_kontrol']));
    }

    public function inpatientPlanSpecialists(Request $request, VClaimService $service): JsonResponse
    {
        $data = $request->validate([
            'no_kartu' => ['required', 'string', 'max:40'],
            'tanggal_kontrol' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json($service->controlPlanSpecialists($data['no_kartu'], $data['tanggal_kontrol'], '1'));
    }

    public function inpatientPlanDoctors(Request $request, VClaimService $service): JsonResponse
    {
        $data = $request->validate([
            'poli_kontrol' => ['required', 'string', 'max:20'],
            'tanggal_kontrol' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json($service->controlPlanDoctors($data['poli_kontrol'], $data['tanggal_kontrol'], '1'));
    }

    public function updateControlPlan(VClaimUpdateRencanaKontrolRequest $request, VClaimService $service, string $noSurat): RedirectResponse
    {
        return Feedback::metadata(function () use ($request, $service, $noSurat): array {
            $result = $service->updateControlPlan(
                $noSurat,
                $request->validated(),
                $this->currentUser($request),
            );

            if ($result['metadata']['code'] === '200') {
                $this->updateLocalControlPlan($noSurat, $request->validated(), $result['skdp']);
            }

            return $result;
        }, 'Rencana kontrol gagal diperbarui.');
    }

    public function destroyControlPlan(Request $request, VClaimService $service, string $noSurat): RedirectResponse
    {
        return Feedback::metadata(function () use ($request, $service, $noSurat): array {
            $result = $service->deleteControlPlan($noSurat, $this->currentUser($request));

            if ($result['metadata']['code'] === '200') {
                DB::table('bridging_surat_kontrol_bpjs')->where('no_surat', $noSurat)->delete();
            }

            return $result;
        }, 'Rencana kontrol gagal dihapus.');
    }

    public function inpatientPlans(VClaimListRencanaKontrolRequest $request, VClaimService $service): Response
    {
        $filters = $this->controlPlanFilters($request);

        return Inertia::render('IntegrasiEksternal/Bpjs/VClaim/RencanaRawatInap', [
            'result' => $service->controlPlans($filters['start_date'], $filters['end_date'], $filters['filter'], 'spri'),
            'filters' => $filters,
        ]);
    }

    public function storeInpatientPlan(VClaimStoreRencanaRawatInapRequest $request, VClaimService $service): RedirectResponse
    {
        return Feedback::metadata(
            fn (): array => $service->createInpatientPlan(
                $request->validated(),
                $this->currentUser($request),
            ),
            'Rencana rawat inap gagal disimpan.',
        );
    }

    public function storeRegistrationInpatientPlan(VClaimStoreSpriRequest $request, VClaimService $service, string $noRawat): RedirectResponse
    {
        return Feedback::metadata(function () use ($request, $service, $noRawat): array {
            $registration = DB::table('reg_periksa as reg')
                ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg.no_rkm_medis')
                ->leftJoin('bridging_sep as sep', 'sep.no_rawat', '=', 'reg.no_rawat')
                ->where('reg.no_rawat', $noRawat)
                ->select([
                    'reg.no_rawat',
                    'reg.no_rkm_medis',
                    'pasien.no_peserta',
                    'sep.no_sep as local_no_sep',
                    'sep.diagawal as local_diagawal',
                    'sep.nmdiagnosaawal as local_nmdiagnosaawal',
                ])
                ->first();

            if ($registration === null) {
                return $this->emptyMetadata('No. Rawat tidak ditemukan.');
            }

            $noKartu = (string) ($registration->no_peserta ?? '');

            if ($noKartu === '') {
                return $this->emptyMetadata('Nomor peserta BPJS pasien tidak ditemukan.');
            }

            $validated = $request->validated();
            $noSep = (string) ($validated['no_sep'] ?? $registration->local_no_sep ?? '');
            $diagnosa = $this->spriDiagnosisLabel($validated);

            $result = $service->createInpatientPlan([
                'no_kartu' => $noKartu,
                'kode_dokter' => $validated['kode_dokter'],
                'poli_kontrol' => $validated['poli_kontrol'],
                'tanggal_kontrol' => $validated['tanggal_kontrol'],
            ], $this->currentUser($request));

            if (($result['metadata']['code'] ?? '') === '200') {
                $this->storeLocalInpatientPlan(
                    $noRawat,
                    $noKartu,
                    $noSep,
                    $diagnosa,
                    $validated,
                    $result['spri'] ?? [],
                );
            }

            return $result;
        }, 'SPRI gagal disimpan.');
    }

    public function updateInpatientPlan(VClaimUpdateRencanaRawatInapRequest $request, VClaimService $service, string $noSurat): RedirectResponse
    {
        return Feedback::metadata(function () use ($request, $service, $noSurat): array {
            $result = $service->updateInpatientPlan(
                $noSurat,
                $request->validated(),
                $this->currentUser($request),
            );

            if ($result['metadata']['code'] === '200') {
                $this->updateLocalInpatientPlan($noSurat, $request->validated(), $result['spri'] ?? []);
            }

            return $result;
        }, 'Rencana rawat inap gagal diperbarui.');
    }

    public function destroyInpatientPlan(Request $request, VClaimService $service, string $noSurat): RedirectResponse
    {
        return Feedback::metadata(function () use ($request, $service, $noSurat): array {
            $result = $service->deleteControlPlan($noSurat, $this->currentUser($request));

            if ($result['metadata']['code'] === '200') {
                DB::table('bridging_surat_pri_bpjs')->where('no_surat', $noSurat)->delete();
            }

            return $result;
        }, 'Rencana rawat inap gagal dihapus.');
    }

    public function pullSep(VClaimTarikSepRequest $request, VClaimService $service): RedirectResponse
    {
        $data = $request->validated();

        if (DB::table('bridging_sep')->where('no_sep', $data['no_sep'])->exists()) {
            return back()->with('toast', [
                'type' => 'warning',
                'message' => 'SEP sudah tersimpan di database lokal.',
            ]);
        }

        $registration = DB::table('reg_periksa')
            ->where('no_rawat', $data['no_rawat'])
            ->first();

        if ($registration === null) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'No. Rawat tidak ditemukan di database.',
            ]);
        }

        return Feedback::metadata(function () use ($request, $service, $data, $registration): array {
            $result = $service->sep($data['no_sep']);

            if ($result['metadata']['code'] === '200' && filled($result['sep'])) {
                $this->storeLocalSep($result['sep'], $data['no_rawat'], (array) $registration, $this->currentUser($request));
            }

            return $result;
        }, 'SEP gagal ditarik.', fn (): string => 'SEP berhasil ditarik dan disimpan ke database lokal.');
    }

    public function destroySep(Request $request, VClaimService $service, string $noSep): RedirectResponse
    {
        return Feedback::metadata(function () use ($request, $service, $noSep): array {
            $result = $service->deleteSep($noSep, $this->currentUser($request));

            if ($result['metadata']['code'] === '200') {
                DB::table('bridging_sep')->where('no_sep', $noSep)->delete();
            }

            return $result;
        }, 'SEP gagal dihapus.');
    }

    public function providerReferences(VClaimService $service, Request $request): JsonResponse
    {
        $query = $request->input('query');
        $type = $request->input('type', '2');

        return response()->json($service->providersReferences($query, $type));
    }

    public function specialistReferences(VClaimService $service, Request $request): JsonResponse
    {
        $query = $request->input('query');

        return response()->json($service->specialistsReferences($query));
    }

    public function diagnosisReferences(VClaimService $service, Request $request): JsonResponse
    {
        $query = $request->input('query');

        return response()->json($service->diagnosisReferences($query));
    }

    /**
     * @return array{start_date: string, end_date: string, filter: string}
     */
    private function controlPlanFilters(VClaimListRencanaKontrolRequest $request): array
    {
        return [
            'start_date' => $request->validated('start_date') ?: today()->toDateString(),
            'end_date' => $request->validated('end_date') ?: today()->toDateString(),
            'filter' => $request->validated('filter') ?: '1',
        ];
    }

    private function currentUser(Request $request): string
    {
        return (string) ($request->user()?->name ?? $request->user()?->getAuthIdentifier() ?? 'SIMRS');
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    private function emptyResult(string $message): array
    {
        return [
            'metadata' => [
                'code' => '200',
                'message' => $message,
            ],
            'response' => [],
            'rows' => [],
        ];
    }

    /**
     * @param  array{no_sep: string, kode_dokter: string, nama_dokter: string, poli_kontrol: string, nama_poli: string, tanggal_kontrol: string}  $input
     * @param  array<string, mixed>  $skdp
     */
    private function storeLocalControlPlan(array $input, array $skdp): void
    {
        $letterNumber = (string) Arr::get($skdp, 'noSuratKontrol', '');

        if ($letterNumber === '') {
            return;
        }

        DB::table('bridging_surat_kontrol_bpjs')->updateOrInsert(
            ['no_surat' => $letterNumber],
            [
                'no_sep' => $input['no_sep'],
                'tgl_surat' => today()->toDateString(),
                'tgl_rencana' => (string) Arr::get($skdp, 'tglRencanaKontrol', $input['tanggal_kontrol']),
                'kd_dokter_bpjs' => $input['kode_dokter'],
                'nm_dokter_bpjs' => $input['nama_dokter'] ?: (string) Arr::get($skdp, 'namaDokter', ''),
                'kd_poli_bpjs' => $input['poli_kontrol'],
                'nm_poli_bpjs' => $input['nama_poli'] ?: $this->localPoliName($input['poli_kontrol']),
            ],
        );
    }

    /**
     * @param  array{kode_dokter: string, nama_dokter?: string|null, poli_kontrol: string, nama_poli?: string|null, tanggal_kontrol: string, diagnosa_awal: string, kd_penyakit?: string|null, mode_diagnosa: string, no_sep?: string|null}  $input
     * @param  array<string, mixed>  $spri
     */
    private function storeLocalInpatientPlan(string $noRawat, string $noKartu, string $noSep, string $diagnosa, array $input, array $spri): void
    {
        $letterNumber = (string) Arr::get($spri, 'noSPRI', '');

        if ($letterNumber === '') {
            return;
        }

        DB::table('bridging_surat_pri_bpjs')->updateOrInsert(
            ['no_surat' => $letterNumber],
            [
                'no_rawat' => $noRawat,
                'no_kartu' => $noKartu,
                'tgl_surat' => today()->toDateString(),
                'tgl_rencana' => $input['tanggal_kontrol'],
                'kd_dokter_bpjs' => $input['kode_dokter'],
                'nm_dokter_bpjs' => (string) Arr::get($spri, 'namaDokter', $input['nama_dokter'] ?? ''),
                'kd_poli_bpjs' => $input['poli_kontrol'],
                'nm_poli_bpjs' => (string) Arr::get($spri, 'namaPoliKontrol', $input['nama_poli'] ?? ''),
                'diagnosa' => $diagnosa,
                'no_sep' => $noSep,
            ],
        );
    }

    /**
     * @param  array{no_sep: string, kode_dokter: string, nama_dokter: string, poli_kontrol: string, nama_poli: string, tanggal_kontrol: string}  $input
     * @param  array<string, mixed>  $skdp
     */
    private function updateLocalControlPlan(string $letterNumber, array $input, array $skdp): void
    {
        DB::table('bridging_surat_kontrol_bpjs')
            ->where('no_surat', $letterNumber)
            ->update([
                'no_sep' => $input['no_sep'],
                'tgl_rencana' => (string) Arr::get($skdp, 'tglRencanaKontrol', $input['tanggal_kontrol']),
                'kd_dokter_bpjs' => $input['kode_dokter'],
                'nm_dokter_bpjs' => $input['nama_dokter'] ?: (string) Arr::get($skdp, 'namaDokter', ''),
                'kd_poli_bpjs' => $input['poli_kontrol'],
                'nm_poli_bpjs' => $input['nama_poli'] ?: $this->localPoliName($input['poli_kontrol']),
            ]);
    }

    /**
     * @param  array{kode_dokter: string, poli_kontrol: string, tanggal_kontrol: string}  $input
     * @param  array<string, mixed>  $spri
     */
    private function updateLocalInpatientPlan(string $letterNumber, array $input, array $spri): void
    {
        $existingPlan = DB::table('bridging_surat_pri_bpjs')
            ->where('no_surat', $letterNumber)
            ->first();
        $fallbackPoliName = $this->localPoliName($input['poli_kontrol']) ?: (string) ($existingPlan?->nm_poli_bpjs ?? '');

        DB::table('bridging_surat_pri_bpjs')
            ->where('no_surat', $letterNumber)
            ->update([
                'tgl_rencana' => (string) Arr::get($spri, 'tglRencanaKontrol', $input['tanggal_kontrol']),
                'kd_dokter_bpjs' => $input['kode_dokter'],
                'nm_dokter_bpjs' => (string) Arr::get($spri, 'namaDokter', $existingPlan?->nm_dokter_bpjs ?? ''),
                'kd_poli_bpjs' => $input['poli_kontrol'],
                'nm_poli_bpjs' => (string) Arr::get($spri, 'namaPoliKontrol', $fallbackPoliName),
            ]);
    }

    /**
     * @param  array{diagnosa_awal: string, kd_penyakit?: string|null, mode_diagnosa: string}  $input
     */
    private function spriDiagnosisLabel(array $input): string
    {
        $diagnosisCode = (string) ($input['kd_penyakit'] ?? '');
        $diagnosisName = trim((string) ($input['diagnosa_awal'] ?? ''));

        if ($diagnosisCode !== '') {
            return trim($diagnosisCode.' - '.$diagnosisName, ' -');
        }

        return $diagnosisName;
    }

    /**
     * @param  array{tanggal_rujukan: string, tanggal_rencana_kunjungan: string, ppk_dirujuk: string, jenis_pelayanan: string, catatan?: string|null, diagnosa_rujukan: string, tipe_rujukan: string, poli_rujukan: string}  $input
     */
    private function storeLocalOutboundReferral(array $input, string $user, array $rujukan): void
    {
        $letterNumber = (string) Arr::get($rujukan, 'noRujukan', '');

        if ($letterNumber === '') {
            return;
        }

        DB::table('bridging_rujukan_bpjs')->updateOrInsert(
            ['no_rujukan' => $letterNumber],
            [
                'no_sep' => $input['no_sep'],
                'tglRujukan' => (string) Arr::get($rujukan, 'tglRujukan', $input['tanggal_rujukan']),
                'tglRencanaKunjungan' => (string) Arr::get($rujukan, 'tglRencanaKunjungan', $input['tanggal_rencana_kunjungan']),
                'ppkDirujuk' => (string) Arr::get($rujukan, 'tujuanRujukan.kode', $input['ppk_dirujuk']),
                'nmPpkDirujuk' => (string) Arr::get($rujukan, 'tujuanRujukan.nama', $input['nama_ppk_dirujuk']),
                'jnsPelayanan' => $input['jenis_pelayanan'],
                'catatan' => $input['catatan'] ?? '',
                'diagRujukan' => (string) Arr::get($rujukan, 'diagnosa.kode', $input['nama_diagnosa_rujukan']),
                'nama_diagRujukan' => (string) Arr::get($rujukan, 'diagnosa.nama', $input['diagnosa_rujukan']),
                'tipeRujukan' => $this->referralTypeLabel($input['tipe_rujukan']),
                'poliRujukan' => (string) Arr::get($rujukan, 'poliTujuan.kode', $input['poli_rujukan']),
                'nama_poliRujukan' => (string) Arr::get($rujukan, 'poliTujuan.nama', $input['nama_poli_rujukan'] ?: $this->localPoliName($input['poli_rujukan'])),
                'user' => $user,
            ],
        );
    }

    /**
     * @param  array{tanggal_rujukan: string, tanggal_rencana_kunjungan: string, ppk_dirujuk: string, jenis_pelayanan: string, catatan?: string|null, diagnosa_rujukan: string, tipe_rujukan: string, poli_rujukan: string}  $input
     */
    private function updateLocalOutboundReferral(string $referralNumber, array $input, string $user): void
    {
        DB::table('bridging_rujukan_bpjs')
            ->where('no_rujukan', $referralNumber)
            ->update([
                'no_sep' => $input['no_sep'],
                'tglRujukan' => $input['tanggal_rujukan'],
                'tglRencanaKunjungan' => $input['tanggal_rencana_kunjungan'],
                'ppkDirujuk' => $input['ppk_dirujuk'],
                'nmPpkDirujuk' => $input['nama_ppk_dirujuk'],
                'jnsPelayanan' => $input['jenis_pelayanan'],
                'catatan' => $input['catatan'] ?? '',
                'diagRujukan' => $input['diagnosa_rujukan'],
                'nama_diagRujukan' => $input['nama_diagnosa_rujukan'],
                'tipeRujukan' => $this->referralTypeLabel($input['tipe_rujukan']),
                'poliRujukan' => $input['poli_rujukan'],
                'nama_poliRujukan' => $input['nama_poli_rujukan'] ?: $this->localPoliName($input['poli_rujukan']),
                'user' => $user,
            ]);
    }

    /**
     * @param  array<string, mixed>  $sep
     * @param  array<string, mixed>  $registration
     */
    private function storeLocalSep(array $sep, string $noRawat, array $registration, string $user): void
    {
        $medicalRecordNumber = (string) ($registration['no_rkm_medis'] ?? '');
        $patient = $medicalRecordNumber !== ''
            ? (array) DB::table('pasien')->where('no_rkm_medis', $medicalRecordNumber)->first()
            : [];

        $serviceType = Arr::get($sep, 'jnsPelayanan') === 'Rawat Jalan' ? '2' : '1';
        $diagnosisName = (string) Arr::get($sep, 'diagnosa', '-');
        $diagnosisCode = (string) (DB::table('penyakit')
            ->where('nm_penyakit', $diagnosisName)
            ->value('kd_penyakit') ?? '-');
        $clinicName = (string) Arr::get($sep, 'poli', '-');
        $clinicCode = (string) (DB::table('maping_poli_bpjs')
            ->where('nm_poli_bpjs', $clinicName)
            ->value('kd_poli_bpjs') ?? '-');
        $gender = substr((string) Arr::get($sep, 'peserta.kelamin', 'L'), 0, 1);

        DB::table('bridging_sep')->insert([
            'no_sep' => (string) Arr::get($sep, 'noSep'),
            'no_rawat' => $noRawat,
            'tglsep' => (string) Arr::get($sep, 'tglSep', today()->toDateString()),
            'jam_sep' => now()->format('H:i:s'),
            'tglrujukan' => (string) Arr::get($sep, 'tglSep', today()->toDateString()),
            'no_rujukan' => (string) Arr::get($sep, 'noRujukan', ''),
            'kdppkrujukan' => $serviceType === '2' ? '-' : (string) config('bpjs.vclaim.ppk'),
            'nmppkrujukan' => $serviceType === '2' ? '-' : (string) config('bpjs.vclaim.facility_name'),
            'kdppkpelayanan' => (string) config('bpjs.vclaim.ppk'),
            'nmppkpelayanan' => (string) config('bpjs.vclaim.facility_name'),
            'jnspelayanan' => $serviceType,
            'catatan' => (string) Arr::get($sep, 'catatan', '-'),
            'diagawal' => $diagnosisCode,
            'nmdiagnosaawal' => $diagnosisCode.'-'.$diagnosisName,
            'kdpolitujuan' => $clinicCode,
            'nmpolitujuan' => $clinicName,
            'klsrawat' => (string) Arr::get($sep, 'klsRawat.klsRawatHak', '3'),
            'klsnaik' => (string) Arr::get($sep, 'klsRawat.klsRawatNaik', ''),
            'pembiayaan' => (string) Arr::get($sep, 'klsRawat.pembiayaan', ''),
            'pjnaikkelas' => (string) Arr::get($sep, 'klsRawat.penanggungJawab', ''),
            'lakalantas' => (string) Arr::get($sep, 'kdStatusKecelakaan', '0'),
            'user' => $user,
            'nomr' => $medicalRecordNumber,
            'nama_pasien' => (string) Arr::get($sep, 'peserta.nama', '-'),
            'tanggal_lahir' => (string) Arr::get($sep, 'peserta.tglLahir', today()->toDateString()),
            'peserta' => (string) Arr::get($sep, 'peserta.jnsPeserta', '-'),
            'jkel' => in_array($gender, ['L', 'P'], true) ? $gender : 'L',
            'no_kartu' => (string) Arr::get($sep, 'peserta.noKartu', ''),
            'tglpulang' => now()->toDateTimeString(),
            'asal_rujukan' => $serviceType === '2' ? '1. Faskes 1' : '2. Faskes 2(RS)',
            'eksekutif' => $this->legacyBooleanEnum(Arr::get($sep, 'poliEksekutif')),
            'cob' => $this->legacyBooleanEnum(Arr::get($sep, 'cob')),
            'notelep' => (string) ($patient['no_tlp'] ?? '-'),
            'katarak' => $this->legacyBooleanEnum(Arr::get($sep, 'katarak')),
            'tglkkl' => (string) Arr::get($sep, 'lokasiKejadian.tglKejadian', Arr::get($sep, 'tglSep', today()->toDateString())),
            'keterangankkl' => (string) Arr::get($sep, 'lokasiKejadian.ketKejadian', ''),
            'suplesi' => '0. Tidak',
            'no_sep_suplesi' => '',
            'kdprop' => (string) Arr::get($sep, 'lokasiKejadian.kdProp', ''),
            'nmprop' => '-',
            'kdkab' => (string) Arr::get($sep, 'lokasiKejadian.kdKab', '0'),
            'nmkab' => '-',
            'kdkec' => (string) Arr::get($sep, 'lokasiKejadian.kdKec', '0'),
            'nmkec' => '-',
            'noskdp' => (string) Arr::get($sep, 'kontrol.noSurat', ''),
            'kddpjp' => (string) Arr::get($sep, 'dpjp.kdDPJP', Arr::get($sep, 'kontrol.kdDokter', '-')),
            'nmdpdjp' => (string) Arr::get($sep, 'dpjp.nmDPJP', Arr::get($sep, 'kontrol.nmDokter', '-')),
            'tujuankunjungan' => (string) Arr::get($sep, 'tujuanKunj.kode', '0'),
            'flagprosedur' => (string) Arr::get($sep, 'flagProcedure.kode', ''),
            'penunjang' => (string) Arr::get($sep, 'kdPenunjang.kode', ''),
            'asesmenpelayanan' => (string) Arr::get($sep, 'assesmentPel.kode', ''),
            'kddpjplayanan' => (string) Arr::get($sep, 'dpjp.kdDPJP', '-'),
            'nmdpjplayanan' => (string) Arr::get($sep, 'dpjp.nmDPJP', '-'),
        ]);
    }

    private function localPoliName(string $bpjsClinicCode): string
    {
        return (string) (DB::table('maping_poli_bpjs')
            ->where('kd_poli_bpjs', $bpjsClinicCode)
            ->value('nm_poli_bpjs') ?? '');
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    private function emptyMetadata(string $message): array
    {
        return [
            'metadata' => [
                'code' => '404',
                'message' => $message,
            ],
            'response' => [],
            'rows' => [],
        ];
    }

    private function legacyBooleanEnum(mixed $value): string
    {
        return in_array($value, ['1', 1, true, '1.Ya'], true) ? '1.Ya' : '0. Tidak';
    }

    private function referralTypeLabel(string $type): string
    {
        return match ($type) {
            '1' => '1. Partial',
            '2' => '2. Rujuk Balik',
            default => '0. Penuh',
        };
    }
}
