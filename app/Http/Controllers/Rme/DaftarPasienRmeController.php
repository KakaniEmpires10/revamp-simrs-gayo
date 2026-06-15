<?php

namespace App\Http\Controllers\Rme;

use App\Http\Controllers\Controller;
use App\Services\Rme\RmeService;
use App\Support\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DaftarPasienRmeController extends Controller
{
    public function rawatJalan(Request $request, RmeService $service): Response
    {
        $filter = $this->filterKunjungan($request, [
            'kd_poli' => ['nullable', 'string', 'max:5'],
            'kd_pj' => ['nullable', 'string', 'max:3'],
            'status' => ['nullable', 'string', 'max:20'],
            'order' => ['nullable', 'in:asc,desc'],
        ]);

        return Inertia::render('Rme/RawatJalan', [
            'filters' => $filter,
            'clinics' => $service->opsiPoliklinik(),
            'payments' => $service->opsiPenjamin(),
            'statuses' => $this->statusRegistrasiOptions(),
            'patients' => Inertia::scroll(fn () => $service->pasienRawatJalan($filter)),
        ]);
    }

    public function rawatInap(Request $request, RmeService $service): Response
    {
        $filter = $this->filterKunjungan($request, [
            'kd_bangsal' => ['nullable', 'string', 'max:5'],
            'tipe_filter_ranap' => ['nullable', 'string', Rule::in(['belum_pulang', 'tanggal_masuk', 'tanggal_keluar'])],
        ]);

        return Inertia::render('Rme/RawatInap', [
            'filters' => $filter,
            'rooms' => $service->opsiBangsal(),
            'doctors' => $service->opsiDokter(),
            'availableRooms' => $service->opsiKamarKosong(),
            'dischargeStatuses' => $service->opsiStatusPulangRanap(),
            'patients' => Inertia::scroll(fn () => $service->pasienRawatInap($filter)),
        ]);
    }

    public function updateDpjpRanap(Request $request, RmeService $service): RedirectResponse
    {
        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:17'],
            'kode_dokter' => ['required', 'array', 'min:1'],
            'kode_dokter.*' => ['required', 'string', 'max:20', Rule::exists('dokter', 'kd_dokter')->where('status', '1')],
        ]);

        return Feedback::hasil(
            fn (): array => $service->simpanDokterPenanggungJawabRanap($validated['no_rawat'], $validated['kode_dokter']),
            'Dokter penanggung jawab rawat inap gagal diperbarui.',
        );
    }

    public function pindahKamarRanap(Request $request, RmeService $service): RedirectResponse
    {
        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:17'],
            'kd_kamar' => ['required', 'string', 'max:15', Rule::exists('kamar', 'kd_kamar')->where('statusdata', '1')],
            'tgl_masuk' => ['required', 'date'],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'diagnosa_awal' => ['nullable', 'string', 'max:100'],
            'opsi_pindah_kamar' => ['required', Rule::in(['1', '2', '3', '4'])],
        ]);

        return Feedback::hasil(
            fn (): array => $service->pindahKamarRanap($validated['no_rawat'], $validated),
            'Pasien gagal dipindahkan kamar.',
        );
    }

    public function batalRanap(Request $request, RmeService $service): RedirectResponse
    {
        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:17'],
        ]);

        return Feedback::hasil(
            fn (): array => $service->batalRanap($validated['no_rawat']),
            'Rawat inap pasien gagal dibatalkan.',
        );
    }

    public function pulangkanRanap(Request $request, RmeService $service): RedirectResponse
    {
        $validated = $this->validasiPulangRanap($request);

        return Feedback::hasil(
            fn (): array => $service->pulangkanRanap($validated['no_rawat'], $validated),
            'Pasien rawat inap gagal dipulangkan.',
        );
    }

    public function updatePulangRanap(Request $request, RmeService $service): RedirectResponse
    {
        $validated = $this->validasiPulangRanap($request);

        return Feedback::hasil(
            fn (): array => $service->ubahPulangRanap($validated['no_rawat'], $validated),
            'Data pulang pasien rawat inap gagal diperbarui.',
        );
    }

    public function updateStatusPulangRanap(Request $request, RmeService $service): RedirectResponse
    {
        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:17'],
            'stts_pulang' => ['required', 'string', Rule::in($this->statusPulangRanapValues())],
        ]);

        return Feedback::hasil(
            fn (): array => $service->ubahStatusPulangRanap($validated['no_rawat'], $validated['stts_pulang']),
            'Status pulang pasien gagal diperbarui.',
        );
    }

    public function ttvPasien(Request $request, RmeService $service): JsonResponse
    {
        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:17', 'exists:reg_periksa,no_rawat'],
        ], $this->pesanValidasiTtv(), $this->atributValidasiTtv());

        return response()->json([
            'ttv' => $service->ttvPasien($validated['no_rawat']),
        ]);
    }

    public function simpanTtvPasien(Request $request, RmeService $service): RedirectResponse
    {
        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:17', 'exists:reg_periksa,no_rawat'],
            ...$this->aturanValidasiTtv(),
        ], $this->pesanValidasiTtv(), $this->atributValidasiTtv());

        return Feedback::hasil(
            fn (): array => $service->simpanTtvPasien($validated, (string) $request->user()?->getAuthIdentifier()),
            'Tanda vital pasien gagal disimpan.',
        );
    }

    public function igd(Request $request, RmeService $service): Response
    {
        $filter = $this->filterKunjungan($request, [
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        return Inertia::render('Rme/Igd', [
            'filters' => $filter,
            'clinics' => $service->opsiPoliklinik(sertakanIgd: true),
            'patients' => Inertia::scroll(fn () => $service->pasienIgd($filter)),
            'statuses' => $this->statusRegistrasiOptions(),
        ]);
    }

    public function updateDokterIgd(Request $request, RmeService $service): RedirectResponse
    {
        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:17'],
            'kd_dokter' => [
                'required',
                'string',
                'max:20',
                Rule::exists('dokter', 'kd_dokter')->where('status', '1'),
            ],
        ]);

        return Feedback::hasil(
            fn (): array => $service->ubahDokterIgd($validated['no_rawat'], $validated['kd_dokter']),
            'Dokter IGD gagal diperbarui.',
        );
    }

    public function updateDiagnosaIgd(Request $request, RmeService $service): RedirectResponse
    {
        $validated = $request->validate([
            'no_rawat' => ['required', 'string', 'max:17'],
            'kd_penyakit' => ['required', 'string', 'max:10', Rule::exists('penyakit', 'kd_penyakit')],
        ]);

        return Feedback::hasil(
            fn (): array => $service->simpanDiagnosaIgd($validated['no_rawat'], $validated['kd_penyakit']),
            'Diagnosa IGD gagal disimpan.',
        );
    }

    public function rujukanInternal(Request $request, RmeService $service): Response
    {
        $filter = $this->filterKunjungan($request, [
            'kd_poli_asal' => ['nullable', 'string', 'max:5'],
            'kd_poli' => ['nullable', 'string', 'max:5'],
            'order' => ['nullable', 'in:asc,desc'],
        ]);

        return Inertia::render('Rme/RujukanInternal', [
            'filters' => $filter,
            'clinics' => $service->opsiPoliklinik(sertakanIgd: true),
            'patients' => Inertia::scroll(fn () => $service->pasienRujukanInternal($filter)),
        ]);
    }

    /**
     * @param  array<string, list<string>>  $additionalRules
     * @return array<string, string|null>
     */
    private function filterKunjungan(Request $request, array $additionalRules = []): array
    {
        $validated = $request->validate([
            'tgl_awal' => ['nullable', 'date'],
            'tgl_akhir' => ['nullable', 'date', 'after_or_equal:tgl_awal'],
            'search' => ['nullable', 'string', 'max:80'],
            ...$additionalRules,
        ]);

        return [
            'tgl_awal' => $validated['tgl_awal'] ?? now()->toDateString(),
            'tgl_akhir' => $validated['tgl_akhir'] ?? now()->toDateString(),
            'search' => $validated['search'] ?? '',
            ...collect($additionalRules)
                ->keys()
                ->mapWithKeys(fn (string $key): array => [$key => $validated[$key] ?? null])
                ->all(),
        ];
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    private function statusRegistrasiOptions(): array
    {
        return [
            ['value' => 'Belum', 'label' => 'Belum'],
            ['value' => 'Sudah', 'label' => 'Sudah'],
            ['value' => 'Batal', 'label' => 'Batal'],
            ['value' => 'Berkas Diterima', 'label' => 'Berkas Diterima'],
            ['value' => 'Dirujuk', 'label' => 'Dirujuk'],
            ['value' => 'Meninggal', 'label' => 'Meninggal'],
            ['value' => 'Dirawat', 'label' => 'Dirawat'],
            ['value' => 'Pulang Paksa', 'label' => 'Pulang Paksa'],
        ];
    }

    /**
     * @return array{no_rawat:string,tgl_keluar:string,jam_keluar:string,stts_pulang:string,diagnosa_akhir:string}
     */
    private function validasiPulangRanap(Request $request): array
    {
        return $request->validate([
            'no_rawat' => ['required', 'string', 'max:17'],
            'tgl_keluar' => ['required', 'date'],
            'jam_keluar' => ['required', 'date_format:H:i'],
            'stts_pulang' => ['required', 'string', Rule::in($this->statusPulangRanapValuesTanpaAktif())],
            'diagnosa_akhir' => ['required', 'string', 'max:100'],
        ]);
    }

    /**
     * @return list<string>
     */
    private function statusPulangRanapValues(): array
    {
        return [
            '-',
            'Sehat',
            'Rujuk',
            'APS',
            '+',
            'Meninggal',
            'Sembuh',
            'Membaik',
            'Pulang Paksa',
            'Pindah Kamar',
            'Status Belum Lengkap',
            'Atas Persetujuan Dokter',
            'Atas Permintaan Sendiri',
            'Isoman',
            'Lain-lain',
        ];
    }

    /**
     * @return list<string>
     */
    private function statusPulangRanapValuesTanpaAktif(): array
    {
        return array_values(array_filter(
            $this->statusPulangRanapValues(),
            fn (string $status): bool => ! in_array($status, ['-', 'Pindah Kamar'], true),
        ));
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function aturanValidasiTtv(): array
    {
        return [
            'suhu_tubuh' => ['nullable', 'regex:/^\d{1,2}(\.\d{1,2})?$/', 'max:5'],
            'tensi' => ['required', 'regex:/^\d{2,3}\/\d{2,3}$/', 'max:8'],
            'nadi' => ['nullable', 'integer', 'min:0', 'max:250'],
            'respirasi' => ['nullable', 'integer', 'min:0', 'max:100'],
            'tinggi' => ['nullable', 'regex:/^\d{1,3}(\.\d)?$/', 'max:5'],
            'berat' => ['nullable', 'regex:/^\d{1,3}(\.\d)?$/', 'max:5'],
            'spo2' => ['nullable', 'integer', 'min:0', 'max:100'],
            'gcs' => ['nullable', 'regex:/^E[1-4]\sV[1-5]\sM[1-6]$/', 'max:10'],
            'kesadaran' => ['required', 'string', Rule::in(['Compos Mentis', 'Somnolence', 'Sopor', 'Coma'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasiTtv(): array
    {
        return [
            'required' => ':attribute wajib diisi sebelum tanda vital disimpan.',
            'exists' => ':attribute tidak ditemukan pada data SIMRS. Muat ulang halaman lalu pilih pasien kembali.',
            'integer' => ':attribute hanya boleh berisi angka.',
            'min' => ':attribute tidak boleh kurang dari :min.',
            'max' => ':attribute terlalu panjang atau melebihi batas :max karakter.',
            'in' => ':attribute tidak valid.',
            'regex' => ':attribute belum sesuai format yang diperbolehkan.',
            'tensi.regex' => 'Tensi wajib memakai format sistolik/diastolik, contoh 120/80. Huruf dan simbol lain tidak diperbolehkan.',
            'suhu_tubuh.regex' => 'Suhu tubuh hanya boleh angka desimal, contoh 36.5.',
            'tinggi.regex' => 'Tinggi badan hanya boleh angka desimal, contoh 165 atau 165.5.',
            'berat.regex' => 'Berat badan hanya boleh angka desimal, contoh 60 atau 60.5.',
            'gcs.regex' => 'GCS wajib memakai format E, V, dan M yang valid, contoh E4 V5 M6.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function atributValidasiTtv(): array
    {
        return [
            'no_rawat' => 'Nomor rawat',
            'suhu_tubuh' => 'Suhu tubuh',
            'tensi' => 'Tensi',
            'nadi' => 'Nadi',
            'respirasi' => 'Respirasi',
            'tinggi' => 'Tinggi badan',
            'berat' => 'Berat badan',
            'spo2' => 'SpO2',
            'gcs' => 'GCS',
            'kesadaran' => 'Kesadaran',
        ];
    }
}
