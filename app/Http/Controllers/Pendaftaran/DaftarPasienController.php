<?php

namespace App\Http\Controllers\Pendaftaran;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pendaftaran\CariPasienRequest;
use App\Http\Requests\Pendaftaran\SimpanPendaftaranUmumRequest;
use App\Http\Requests\Pendaftaran\SimpanPindahRawatInapRequest;
use App\Http\Requests\Pendaftaran\SimpanRujukanInternalRequest;
use App\Http\Requests\Pendaftaran\UbahPendaftaranUmumRequest;
use App\Modules\Bpjs\Services\VClaimService;
use App\Services\Pendaftaran\PendaftaranService;
use App\Support\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DaftarPasienController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'view' => ['nullable', 'in:table'],
            'jenis_pendaftaran' => ['nullable', 'in:rawat_jalan,igd'],
            'tgl_awal' => ['nullable', 'date'],
            'tgl_akhir' => ['nullable', 'date', 'after_or_equal:tgl_awal'],
            'kd_poli' => ['nullable', 'string', 'max:5'],
            'search' => ['nullable', 'string', 'max:60'],
        ]);

        $view = $filters['view'] ?? 'registration';
        $registrationType = $filters['jenis_pendaftaran'] ?? 'rawat_jalan';
        $tableFilters = [
            'view' => $view,
            'jenis_pendaftaran' => $registrationType,
            'tgl_awal' => $filters['tgl_awal'] ?? now()->toDateString(),
            'tgl_akhir' => $filters['tgl_akhir'] ?? now()->toDateString(),
            'kd_poli' => $registrationType === 'igd' ? null : ($filters['kd_poli'] ?? null),
            'search' => $filters['search'] ?? '',
        ];

        return Inertia::render('Pendaftaran/DaftarPasien', [
            'doctors' => $this->doctorOptions(),
            'clinics' => $this->clinicOptions(),
            'payments' => $this->paymentOptions(),
            'igdClinicCode' => 'IGDK',
            'defaultPaymentCode' => $this->defaultPaymentCode(),
            'view' => $view,
            'filters' => $tableFilters,
            'registeredPatients' => $view === 'table'
                ? Inertia::scroll(fn () => $this->registeredPatients($tableFilters))
                : null,
        ]);
    }

    public function search(CariPasienRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $column = match ($validated['mode']) {
            'nik' => 'no_ktp',
            'no_peserta' => 'no_peserta',
            'no_rm' => 'no_rkm_medis',
        };

        $patients = DB::table('pasien')
            ->select([
                'no_rkm_medis',
                'nm_pasien',
                'no_ktp',
                'no_peserta',
                'jk',
                'tgl_lahir',
                'alamat',
                'no_tlp',
                'namakeluarga',
                'keluarga',
                'alamatpj',
                'kd_pj',
            ])
            ->where($column, 'like', $validated['query'].'%')
            ->orderBy($column)
            ->limit(10)
            ->get();

        return response()->json([
            'data' => $patients,
        ]);
    }

    public function store(
        SimpanPendaftaranUmumRequest $request,
        PendaftaranService $service
    ): RedirectResponse {
        return Feedback::mutasi(
            function () use ($request, $service): string {
                $registration = $service->simpan($request->validated());

                return "Pasien berhasil didaftarkan. No rawat: {$registration['no_rawat']}.";
            },
            'Pasien berhasil didaftarkan.',
            'Pasien gagal didaftarkan.',
        );
    }

    public function storeInternalReferral(
        SimpanRujukanInternalRequest $request,
        PendaftaranService $service,
        string $noRawat
    ): RedirectResponse {
        return Feedback::hasil(
            fn (): array => $service->simpanRujukanInternal($noRawat, $request->validated()),
            'Rujukan internal gagal disimpan.',
        );
    }

    public function storeInpatientTransfer(
        SimpanPindahRawatInapRequest $request,
        PendaftaranService $service,
        string $noRawat
    ): RedirectResponse {
        return Feedback::hasil(
            fn (): array => $service->simpanPindahRawatInap($noRawat, $request->validated()),
            'Pindah rawat inap gagal disimpan.',
        );
    }

    public function destroy(
        Request $request,
        PendaftaranService $service,
        string $noRawat
    ): RedirectResponse {
        return Feedback::hasil(
            fn (): array => $service->hapus($noRawat, $this->currentUser($request)),
            'Pendaftaran gagal dihapus.',
        );
    }

    public function update(
        UbahPendaftaranUmumRequest $request,
        PendaftaranService $service,
        string $noRawat
    ): RedirectResponse {
        return Feedback::hasil(
            fn (): array => $service->ubah($noRawat, $request->validated()),
            'Pendaftaran gagal diperbarui.',
        );
    }

    public function cancel(
        Request $request,
        PendaftaranService $service,
        string $noRawat
    ): RedirectResponse {
        return Feedback::hasil(
            fn (): array => $service->batal($noRawat, $this->currentUser($request)),
            'Pendaftaran gagal dibatalkan.',
        );
    }

    public function reference(Request $request, VClaimService $vclaimService): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:doctor,clinic,payment,room,diagnosis,spri_clinic,spri_doctor'],
            'query' => ['nullable', 'string', 'max:40'],
            'registration_type' => ['nullable', 'in:rawat_jalan,igd'],
            'clinic_code' => ['nullable', 'string', 'max:5'],
            'registration_date' => ['nullable', 'date'],
            'no_kartu' => ['nullable', 'string', 'max:25'],
            'poli_kontrol' => ['nullable', 'string', 'max:20'],
            'tanggal_kontrol' => ['nullable', 'date'],
        ]);

        $query = $validated['query'] ?? '';

        $data = match ($validated['type']) {
            'doctor' => $this->doctorOptions(
                $query,
                $validated['clinic_code'] ?? null,
                $validated['registration_date'] ?? null,
            ),
            'clinic' => $this->clinicOptions($query, $validated['registration_type'] ?? 'rawat_jalan'),
            'payment' => $this->paymentOptions($query),
            'room' => $this->roomOptions($query),
            'diagnosis' => $this->diagnosisOptions($query),
            'spri_clinic' => $this->spriClinicOptions(
                $vclaimService,
                (string) ($validated['no_kartu'] ?? ''),
                (string) ($validated['tanggal_kontrol'] ?? ''),
            ),
            'spri_doctor' => $this->spriDoctorOptions(
                $vclaimService,
                (string) ($validated['poli_kontrol'] ?? ''),
                (string) ($validated['tanggal_kontrol'] ?? ''),
            ),
        };

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * @return list<array{value:string,label:string,description?:string}>
     */
    private function doctorOptions(string $query = '', ?string $clinicCode = null, ?string $registrationDate = null): array
    {
        if ($clinicCode === null || $clinicCode === '') {
            return DB::table('dokter')
                ->select('kd_dokter', 'nm_dokter')
                ->where('status', '1')
                ->when($query !== '', fn ($builder) => $builder->where('nm_dokter', 'like', "%{$query}%"))
                ->orderBy('nm_dokter')
                ->limit(50)
                ->get()
                ->map(fn ($doctor): array => [
                    'value' => $doctor->kd_dokter,
                    'label' => $doctor->nm_dokter,
                ])
                ->all();
        }

        $dayName = $this->scheduleDayName($registrationDate ?: now()->toDateString());

        return DB::table('jadwal')
            ->join('dokter', 'dokter.kd_dokter', '=', 'jadwal.kd_dokter')
            ->select([
                'jadwal.kd_dokter',
                'dokter.nm_dokter',
                'jadwal.hari_kerja',
                'jadwal.jam_mulai',
                'jadwal.jam_selesai',
                'jadwal.kuota',
            ])
            ->where('jadwal.kd_poli', $clinicCode)
            ->where('jadwal.hari_kerja', $dayName)
            ->where('dokter.status', '1')
            ->when($query !== '', fn ($builder) => $builder->where('dokter.nm_dokter', 'like', "%{$query}%"))
            ->orderBy('jadwal.jam_mulai')
            ->orderBy('dokter.nm_dokter')
            ->limit(50)
            ->get()
            ->map(fn ($doctor): array => [
                'value' => $doctor->kd_dokter,
                'label' => $doctor->nm_dokter,
                'description' => "{$doctor->hari_kerja}, ".substr((string) $doctor->jam_mulai, 0, 5).'-'.substr((string) $doctor->jam_selesai, 0, 5)." | Kuota {$doctor->kuota}",
            ])
            ->all();
    }

    private function scheduleDayName(string $date): string
    {
        return match (Carbon::parse($date)->dayOfWeekIso) {
            1 => 'SENIN',
            2 => 'SELASA',
            3 => 'RABU',
            4 => 'KAMIS',
            5 => 'JUMAT',
            6 => 'SABTU',
            default => 'AKHAD',
        };
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    private function clinicOptions(string $query = '', string $registrationType = 'rawat_jalan'): array
    {
        return DB::table('poliklinik')
            ->select('kd_poli', 'nm_poli')
            ->where('status', '1')
            ->when(
                $registrationType === 'igd',
                fn ($builder) => $builder->where('kd_poli', 'IGDK'),
                fn ($builder) => $builder->where('kd_poli', '!=', 'IGDK'),
            )
            ->when($query !== '', fn ($builder) => $builder->where('nm_poli', 'like', "%{$query}%"))
            ->orderBy('nm_poli')
            ->limit(50)
            ->get()
            ->map(fn ($clinic): array => [
                'value' => $clinic->kd_poli,
                'label' => $clinic->nm_poli,
            ])
            ->all();
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    private function paymentOptions(string $query = ''): array
    {
        return DB::table('penjab')
            ->select('kd_pj', 'png_jawab')
            ->where('status', '1')
            ->when($query !== '', fn ($builder) => $builder->where('png_jawab', 'like', "%{$query}%"))
            ->orderByRaw("CASE WHEN png_jawab = 'UMUM' THEN 0 ELSE 1 END")
            ->orderBy('png_jawab')
            ->limit(50)
            ->get()
            ->map(fn ($payment): array => [
                'value' => $payment->kd_pj,
                'label' => $payment->png_jawab,
            ])
            ->all();
    }

    /**
     * @return list<array{value:string,label:string,description?:string,kelas?:string,tarif?:float,status?:string}>
     */
    private function roomOptions(string $query = ''): array
    {
        return DB::table('kamar')
            ->leftJoin('bangsal', 'bangsal.kd_bangsal', '=', 'kamar.kd_bangsal')
            ->select([
                'kamar.kd_kamar',
                'kamar.kelas',
                'kamar.status',
                'kamar.trf_kamar',
                'bangsal.nm_bangsal',
            ])
            ->where('kamar.statusdata', '1')
            ->where('kamar.status', 'KOSONG')
            ->when($query !== '', function ($builder) use ($query): void {
                $builder->where(function ($builder) use ($query): void {
                    $builder
                        ->where('kamar.kd_kamar', 'like', "%{$query}%")
                        ->orWhere('bangsal.nm_bangsal', 'like', "%{$query}%")
                        ->orWhere('kamar.kelas', 'like', "%{$query}%");
                });
            })
            ->orderBy('bangsal.nm_bangsal')
            ->orderBy('kamar.kelas')
            ->orderBy('kamar.kd_kamar')
            ->limit(50)
            ->get()
            ->map(fn ($room): array => [
                'value' => $room->kd_kamar,
                'label' => $room->kd_kamar,
                'description' => trim(($room->nm_bangsal ?? 'Bangsal tidak tercatat').' | '.($room->kelas ?? '-').' | Rp '.number_format((float) $room->trf_kamar, 0, ',', '.')),
                'kelas' => $room->kelas,
                'tarif' => (float) $room->trf_kamar,
                'status' => $room->status,
            ])
            ->all();
    }

    /**
     * @return list<array{value:string,label:string,description?:string}>
     */
    private function diagnosisOptions(string $query = ''): array
    {
        if (mb_strlen($query) < 2) {
            return [];
        }

        return DB::table('penyakit')
            ->select(['kd_penyakit', 'nm_penyakit'])
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('kd_penyakit', 'like', "{$query}%")
                    ->orWhere('nm_penyakit', 'like', "%{$query}%");
            })
            ->orderByRaw('CASE WHEN kd_penyakit LIKE ? THEN 0 ELSE 1 END', [$query.'%'])
            ->orderBy('kd_penyakit')
            ->limit(20)
            ->get()
            ->map(fn ($diagnosis): array => [
                'value' => $diagnosis->kd_penyakit,
                'label' => $diagnosis->kd_penyakit.' - '.$diagnosis->nm_penyakit,
                'description' => $diagnosis->nm_penyakit,
            ])
            ->all();
    }

    private function defaultPaymentCode(): string
    {
        return (string) DB::table('penjab')
            ->where('status', '1')
            ->where('png_jawab', 'UMUM')
            ->value('kd_pj');
    }

    private function currentUser(Request $request): string
    {
        $user = $request->user();

        return (string) ($user?->name ?? $user?->email ?? 'SIMRS');
    }

    /**
     * @return list<array{value:string,label:string,description?:string}>
     */
    private function spriClinicOptions(VClaimService $service, string $noKartu, string $controlDate): array
    {
        if (! filled($noKartu) || ! filled($controlDate)) {
            return [];
        }

        $result = $service->controlPlanSpecialists(preg_replace('/\D/', '', $noKartu), $controlDate, '1');

        if (($result['metadata']['code'] ?? null) !== '200') {
            return [];
        }

        return collect($result['rows'] ?? [])
            ->map(fn (array $row): array => [
                'value' => (string) ($row['kodePoli'] ?? $row['kdPoli'] ?? $row['poliKontrol'] ?? ''),
                'label' => (string) ($row['namaPoli'] ?? $row['nmPoli'] ?? $row['nama'] ?? ''),
                'description' => (string) ($row['kapasitas'] ?? $row['jmlRencanaKontroldanRujukan'] ?? ''),
            ])
            ->filter(fn (array $row): bool => $row['value'] !== '' && $row['label'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return list<array{value:string,label:string,description?:string}>
     */
    private function spriDoctorOptions(VClaimService $service, string $clinicCode, string $controlDate): array
    {
        if (! filled($clinicCode) || ! filled($controlDate)) {
            return [];
        }

        $result = $service->controlPlanDoctors($clinicCode, $controlDate, '1');

        if (($result['metadata']['code'] ?? null) !== '200') {
            return [];
        }

        return collect($result['rows'] ?? [])
            ->map(fn (array $row): array => [
                'value' => (string) ($row['kodeDokter'] ?? $row['kdDokter'] ?? ''),
                'label' => (string) ($row['namaDokter'] ?? $row['nmDokter'] ?? $row['nama'] ?? ''),
                'description' => (string) ($row['jadwalPraktek'] ?? $row['jamPraktek'] ?? ''),
            ])
            ->filter(fn (array $row): bool => $row['value'] !== '' && $row['label'] !== '')
            ->values()
            ->all();
    }

    private function localPoliName(string $bpjsClinicCode): string
    {
        return (string) (DB::table('maping_poli_bpjs')
            ->where('kd_poli_bpjs', $bpjsClinicCode)
            ->value('nm_poli_bpjs') ?? '');
    }

    /**
     * @param  array{jenis_pendaftaran:string,tgl_awal:string,tgl_akhir:string,kd_poli:?string,search:string}  $filters
     */
    private function registeredPatients(array $filters): mixed
    {
        return DB::table('reg_periksa as reg')
            ->select([
                'reg.no_reg',
                'reg.no_rawat',
                'reg.tgl_registrasi',
                'reg.jam_reg',
                'reg.kd_dokter',
                'reg.no_rkm_medis',
                'reg.kd_poli',
                'reg.p_jawab',
                'reg.almt_pj',
                'reg.hubunganpj',
                'reg.stts',
                'reg.stts_daftar',
                'reg.status_lanjut',
                'reg.status_bayar',
                'reg.kd_pj',
                'pasien.nm_pasien',
                'pasien.jk',
                'pasien.no_tlp',
                'pasien.no_peserta',
                'pasien.no_ktp',
                'pasien.tgl_lahir',
                'penjab.png_jawab',
                'dokter.nm_dokter',
                'poli.nm_poli',
                'rujuk.perujuk',
                'rujuk.kategori_rujuk',
                'mjkn.no_rawat as no_rawat_mjkn',
                'sep.no_sep',
                'sep.tglsep',
                'sep.klsrawat',
                'sep.diagawal',
                'sep.nmdiagnosaawal',
            ])
            ->join('pasien', 'pasien.no_rkm_medis', '=', 'reg.no_rkm_medis')
            ->join('penjab', 'penjab.kd_pj', '=', 'reg.kd_pj')
            ->join('dokter', 'dokter.kd_dokter', '=', 'reg.kd_dokter')
            ->join('poliklinik as poli', 'poli.kd_poli', '=', 'reg.kd_poli')
            ->leftJoin('rujuk_masuk as rujuk', 'rujuk.no_rawat', '=', 'reg.no_rawat')
            ->leftJoin('referensi_mobilejkn_bpjs as mjkn', 'mjkn.no_rawat', '=', 'reg.no_rawat')
            ->leftJoin('bridging_sep as sep', 'sep.no_rawat', '=', 'reg.no_rawat')
            ->selectSub(
                DB::table('kamar_inap as ranap')
                    ->select('ranap.diagnosa_awal')
                    ->whereColumn('ranap.no_rawat', 'reg.no_rawat')
                    ->orderByDesc('ranap.tgl_masuk')
                    ->orderByDesc('ranap.jam_masuk')
                    ->limit(1),
                'diagnosa_ranap_awal',
            )
            ->selectSub(
                DB::table('kamar_inap as ranap')
                    ->select('ranap.tgl_masuk')
                    ->whereColumn('ranap.no_rawat', 'reg.no_rawat')
                    ->orderByDesc('ranap.tgl_masuk')
                    ->orderByDesc('ranap.jam_masuk')
                    ->limit(1),
                'tgl_masuk_ranap',
            )
            ->whereBetween('reg.tgl_registrasi', [$filters['tgl_awal'], $filters['tgl_akhir']])
            ->when(
                $filters['jenis_pendaftaran'] === 'igd',
                fn ($query) => $query->where('reg.kd_poli', 'IGDK'),
                fn ($query) => $query->where('reg.kd_poli', '!=', 'IGDK'),
            )
            ->when(
                $filters['jenis_pendaftaran'] !== 'igd' && filled($filters['kd_poli']),
                fn ($query) => $query->where('reg.kd_poli', $filters['kd_poli'])
            )
            ->when(filled($filters['search']), function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('reg.no_rawat', 'like', "%{$search}%")
                        ->orWhere('reg.no_rkm_medis', 'like', "{$search}%")
                        ->orWhere('pasien.nm_pasien', 'like', "%{$search}%")
                        ->orWhere('pasien.no_ktp', 'like', "{$search}%")
                        ->orWhere('pasien.no_peserta', 'like', "{$search}%");
                });
            })
            ->orderByDesc('reg.tgl_registrasi')
            ->orderByDesc('reg.jam_reg')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($patient): array => [
                'no_reg' => $patient->no_reg,
                'no_rawat' => $patient->no_rawat,
                'tgl_registrasi' => $patient->tgl_registrasi,
                'jam_reg' => $patient->jam_reg,
                'kd_dokter' => $patient->kd_dokter,
                'no_rkm_medis' => $patient->no_rkm_medis,
                'kd_poli' => $patient->kd_poli,
                'nm_poli' => $patient->nm_poli,
                'p_jawab' => $patient->p_jawab,
                'almt_pj' => $patient->almt_pj,
                'hubunganpj' => $patient->hubunganpj,
                'stts' => $patient->stts,
                'stts_daftar' => $patient->stts_daftar,
                'status_lanjut' => $patient->status_lanjut,
                'status_bayar' => $patient->status_bayar,
                'kd_pj' => $patient->kd_pj,
                'nm_pasien' => $patient->nm_pasien,
                'jk' => $patient->jk,
                'no_tlp' => $patient->no_tlp,
                'no_peserta' => $patient->no_peserta,
                'no_ktp' => $patient->no_ktp,
                'tgl_lahir' => $patient->tgl_lahir,
                'png_jawab' => $patient->png_jawab,
                'nm_dokter' => $patient->nm_dokter,
                'perujuk' => $patient->perujuk,
                'kategori_rujuk' => $patient->kategori_rujuk,
                'no_sep' => $patient->no_sep,
                'tgl_sep' => $patient->tglsep,
                'klsrawat' => $patient->klsrawat,
                'diagawal' => $patient->diagawal,
                'nmdiagnosaawal' => $patient->nmdiagnosaawal,
                'diagnosa_ranap_awal' => $patient->diagnosa_ranap_awal,
                'tgl_masuk_ranap' => $patient->tgl_masuk_ranap,
                'is_ranap' => $patient->status_lanjut === 'Ranap',
                'is_mjkn' => $patient->no_rawat_mjkn !== null,
            ]);
    }
}
