<?php

namespace App\Http\Controllers\Rme;

use App\Http\Controllers\Controller;
use App\Models\Settings\UserSetting;
use App\Services\Rme\PemeriksaanRmeService;
use App\Support\Feedback;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PemeriksaanPasienController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('rme.pemeriksaan.cppt', [
            'no_rawat' => $request->string('no_rawat')->toString(),
            'fr' => $request->string('fr')->toString() ?: $request->string('asal')->toString() ?: null,
        ]);
    }

    public function cppt(Request $request, PemeriksaanRmeService $service): Response|RedirectResponse
    {
        $noRawat = $request->string('no_rawat')->toString();
        $asal = $request->string('fr')->toString() ?: $request->string('asal')->toString() ?: null;
        $patient = $service->konteksPasien($noRawat, $asal);

        if ($patient === null) {
            return redirect()
                ->route('rme.rawat-jalan.index')
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'Data kunjungan pasien tidak ditemukan. Buka pasien dari daftar RME kembali.',
                ]);
        }

        return Inertia::render('Rme/Pemeriksaan/Cppt', [
            'patient' => $patient,
            'menus' => $service->menuPemeriksaan($noRawat, $patient['asal']),
            'activeMenu' => 'cppt',
            'navigationMode' => UserSetting::pemeriksaanNavigationMode($request->user()?->getAuthIdentifier()),
            'formDefaults' => $service->defaultCppt($request->user(), $noRawat),
            'canChooseOfficer' => $service->dapatMemilihPengisi($request->user()),
            'officerOptions' => $service->dapatMemilihPengisi($request->user()) ? $service->opsiPengisi() : [],
            'cpptHistoryFilters' => $this->filterRiwayatCppt($request),
            'cpptHistoryLimit' => $this->limitRiwayatCppt($request),
            'cpptHistories' => fn () => $service->riwayatCppt(
                (string) $patient['no_rkm_medis'],
                $this->limitRiwayatCppt($request),
                [
                    ...$this->filterRiwayatCppt($request),
                    'no_rawat' => $noRawat,
                ],
                $request->user(),
            ),
            'riwayatUrls' => [
                'lastThree' => route('rme.pemeriksaan.riwayat', [
                    'no_rawat' => $noRawat,
                    'fr' => $service->kodeAsal($patient['asal']),
                    'limit' => 3,
                ]),
                'all' => route('rme.pemeriksaan.riwayat', [
                    'no_rawat' => $noRawat,
                    'fr' => $service->kodeAsal($patient['asal']),
                ]),
            ],
        ]);
    }

    public function storeCppt(Request $request, PemeriksaanRmeService $service): RedirectResponse
    {
        return Feedback::mutasi(
            function () use ($request, $service): string {
                $data = Validator::make($request->all(), [
                    'no_rawat' => ['required', 'string', 'max:17', 'exists:reg_periksa,no_rawat'],
                    'asal' => ['nullable', 'string', Rule::in(['rawat-jalan', 'igd', 'rawat-inap', 'rujukan-internal', 'rj', 'ri', 'rp'])],
                    'tgl_perawatan' => ['required', 'date_format:Y-m-d'],
                    'jam_rawat' => ['required', 'date_format:H:i'],
                    'nip' => ['required', 'string', 'max:20', 'exists:pegawai,nik'],
                    'suhu_tubuh' => ['nullable', 'regex:/^\d{1,2}(\.\d{1,2})?$/', 'max:5'],
                    'tensi' => ['required', 'regex:/^\d{2,3}\/\d{2,3}$/', 'max:8'],
                    'nadi' => ['nullable', 'integer', 'min:0', 'max:250'],
                    'respirasi' => ['nullable', 'integer', 'min:0', 'max:100'],
                    'tinggi' => ['nullable', 'regex:/^\d{1,3}(\.\d)?$/', 'max:5'],
                    'berat' => ['nullable', 'regex:/^\d{1,3}(\.\d)?$/', 'max:5'],
                    'spo2' => ['nullable', 'integer', 'min:0', 'max:100'],
                    'gcs' => ['nullable', 'regex:/^E[1-4]\sV[1-5]\sM[1-6]$/', 'max:10'],
                    'kesadaran' => ['required', 'string', Rule::in(['Compos Mentis', 'Somnolence', 'Sopor', 'Coma'])],
                    'keluhan' => ['required', 'string', 'max:1000'],
                    'pemeriksaan' => ['required', 'string', 'max:1000'],
                    'alergi' => ['nullable', 'string', 'max:50'],
                    'imun_ke' => ['nullable', 'string', 'max:20'],
                    'lingkar_perut' => ['nullable', 'string', 'max:5'],
                    'rtl' => ['nullable', 'string', 'max:1000'],
                    'penilaian' => ['required', 'string', 'max:1000'],
                    'instruksi' => ['nullable', 'string', 'max:1000'],
                    'evaluasi' => ['nullable', 'string', 'max:1000'],
                ], $this->pesanValidasiCppt(), $this->atributValidasiCppt())->validate();

                $service->simpanCppt($request->user(), $data, (string) $service->normalisasiAsal($data['asal'] ?? 'rawat-jalan'));

                return 'CPPT pasien berhasil disimpan.';
            },
            'CPPT pasien berhasil disimpan.',
            'CPPT pasien gagal disimpan.'
        );
    }

    public function updateCppt(Request $request, PemeriksaanRmeService $service): RedirectResponse
    {
        return Feedback::mutasi(
            function () use ($request, $service): string {
                $data = Validator::make($request->all(), [
                    'key_no_rawat' => ['required', 'string', 'max:17', 'exists:reg_periksa,no_rawat'],
                    'key_tgl_perawatan' => ['required', 'date_format:Y-m-d'],
                    'key_jam_rawat' => ['required', 'date_format:H:i:s'],
                    'key_sumber' => ['required', Rule::in(['pemeriksaan_ralan', 'pemeriksaan_ranap'])],
                    'nip' => ['required', 'string', 'max:20', 'exists:pegawai,nik'],
                    'suhu_tubuh' => ['nullable', 'regex:/^\d{1,2}(\.\d{1,2})?$/', 'max:5'],
                    'tensi' => ['required', 'regex:/^\d{2,3}\/\d{2,3}$/', 'max:8'],
                    'nadi' => ['nullable', 'integer', 'min:0', 'max:250'],
                    'respirasi' => ['nullable', 'integer', 'min:0', 'max:100'],
                    'tinggi' => ['nullable', 'regex:/^\d{1,3}(\.\d)?$/', 'max:5'],
                    'berat' => ['nullable', 'regex:/^\d{1,3}(\.\d)?$/', 'max:5'],
                    'spo2' => ['nullable', 'integer', 'min:0', 'max:100'],
                    'gcs' => ['nullable', 'regex:/^E[1-4]\sV[1-5]\sM[1-6]$/', 'max:10'],
                    'kesadaran' => ['required', 'string', Rule::in(['Compos Mentis', 'Somnolence', 'Sopor', 'Coma'])],
                    'keluhan' => ['required', 'string', 'max:1000'],
                    'pemeriksaan' => ['required', 'string', 'max:2000'],
                    'alergi' => ['nullable', 'string', 'max:50'],
                    'imun_ke' => ['nullable', 'string', 'max:20'],
                    'lingkar_perut' => ['nullable', 'string', 'max:5'],
                    'rtl' => ['nullable', 'string', 'max:1000'],
                    'penilaian' => ['required', 'string', 'max:1000'],
                    'instruksi' => ['nullable', 'string', 'max:1000'],
                    'evaluasi' => ['nullable', 'string', 'max:1000'],
                ], $this->pesanValidasiCppt(), $this->atributValidasiCppt())->validate();

                $service->ubahCppt($request->user(), $data);

                return 'CPPT pasien berhasil diperbarui.';
            },
            'CPPT pasien berhasil diperbarui.',
            'CPPT pasien gagal diperbarui.'
        );
    }

    public function destroyCppt(Request $request, PemeriksaanRmeService $service): RedirectResponse
    {
        return Feedback::mutasi(
            function () use ($request, $service): string {
                $data = Validator::make($request->all(), [
                    'key_no_rawat' => ['required', 'string', 'max:17', 'exists:reg_periksa,no_rawat'],
                    'key_tgl_perawatan' => ['required', 'date_format:Y-m-d'],
                    'key_jam_rawat' => ['required', 'date_format:H:i:s'],
                    'key_sumber' => ['required', Rule::in(['pemeriksaan_ralan', 'pemeriksaan_ranap'])],
                ], $this->pesanValidasiCppt(), $this->atributValidasiCppt())->validate();

                $service->hapusCppt($request->user(), $data);

                return 'CPPT pasien berhasil dihapus.';
            },
            'CPPT pasien berhasil dihapus.',
            'CPPT pasien gagal dihapus.'
        );
    }

    public function riwayat(Request $request, PemeriksaanRmeService $service): Response|RedirectResponse
    {
        $noRawat = $request->string('no_rawat')->toString();
        $asal = $request->string('fr')->toString() ?: $request->string('asal')->toString() ?: null;
        $patient = $service->konteksPasien($noRawat, $asal);

        if ($patient === null) {
            return redirect()
                ->route('rme.rawat-jalan.index')
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'Data kunjungan pasien tidak ditemukan. Riwayat belum dapat dibuka.',
                ]);
        }

        $limit = $request->integer('limit') > 0 ? $request->integer('limit') : null;

        return Inertia::render('Rme/Pemeriksaan/Riwayat', [
            'patient' => $patient,
            'limit' => $limit,
            'histories' => $service->riwayatPasien((string) $patient['no_rkm_medis'], $limit),
        ]);
    }

    public function placeholder(Request $request, string $menu, PemeriksaanRmeService $service): Response|RedirectResponse
    {
        $noRawat = $request->string('no_rawat')->toString();
        $asal = $request->string('fr')->toString() ?: $request->string('asal')->toString() ?: null;
        $patient = $service->konteksPasien($noRawat, $asal);

        if ($patient === null) {
            return redirect()
                ->route('rme.rawat-jalan.index')
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'Data kunjungan pasien tidak ditemukan. Buka pasien dari daftar RME kembali.',
                ]);
        }

        $menus = $service->menuPemeriksaan($noRawat, $patient['asal']);
        $activeMenu = collect($menus)->firstWhere('key', $menu);

        abort_if($activeMenu === null || $menu === 'cppt', 404);

        return Inertia::render('Rme/Pemeriksaan/Placeholder', [
            'patient' => $patient,
            'menus' => $menus,
            'activeMenu' => $menu,
            'navigationMode' => UserSetting::pemeriksaanNavigationMode($request->user()?->getAuthIdentifier()),
            'riwayatUrls' => [
                'lastThree' => route('rme.pemeriksaan.riwayat', [
                    'no_rawat' => $noRawat,
                    'fr' => $service->kodeAsal($patient['asal']),
                    'limit' => 3,
                ]),
                'all' => route('rme.pemeriksaan.riwayat', [
                    'no_rawat' => $noRawat,
                    'fr' => $service->kodeAsal($patient['asal']),
                ]),
            ],
        ]);
    }

    /**
     * @return array{scope:string,tgl_awal:string|null,tgl_akhir:string|null}
     */
    private function filterRiwayatCppt(Request $request): array
    {
        $scope = $request->string('riwayat_scope')->toString() === 'rm' ? 'rm' : 'kunjungan';
        $today = CarbonImmutable::now('Asia/Jakarta');

        return [
            'scope' => $scope,
            'tgl_awal' => $scope === 'rm'
                ? $request->date('riwayat_tgl_awal')?->toDateString() ?? $today->subDays(30)->toDateString()
                : null,
            'tgl_akhir' => $scope === 'rm'
                ? $request->date('riwayat_tgl_akhir')?->toDateString() ?? $today->toDateString()
                : null,
        ];
    }

    private function limitRiwayatCppt(Request $request): ?int
    {
        return $request->string('riwayat_limit')->toString() === 'all' ? null : 3;
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasiCppt(): array
    {
        return [
            'required' => ':attribute wajib diisi sebelum CPPT disimpan.',
            'date_format' => ':attribute harus memakai format yang sesuai.',
            'exists' => ':attribute tidak ditemukan pada data SIMRS. Muat ulang halaman lalu pilih kembali.',
            'in' => ':attribute tidak valid.',
            'integer' => ':attribute hanya boleh berisi angka.',
            'min' => ':attribute tidak boleh kurang dari :min.',
            'max' => ':attribute terlalu panjang atau melebihi batas :max karakter.',
            'regex' => ':attribute belum sesuai format yang diperbolehkan.',
            'alergi.max' => 'Alergi maksimal 50 karakter karena kolom database hanya varchar(50). Persingkat daftar alergi.',
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
    private function atributValidasiCppt(): array
    {
        return [
            'no_rawat' => 'Nomor rawat',
            'key_no_rawat' => 'Nomor rawat CPPT',
            'key_tgl_perawatan' => 'Tanggal CPPT',
            'key_jam_rawat' => 'Jam CPPT',
            'key_sumber' => 'Sumber data CPPT',
            'tgl_perawatan' => 'Tanggal pemeriksaan',
            'jam_rawat' => 'Jam pemeriksaan',
            'nip' => 'Pengisi pemeriksaan',
            'suhu_tubuh' => 'Suhu tubuh',
            'tensi' => 'Tensi',
            'nadi' => 'Nadi',
            'respirasi' => 'Respirasi',
            'tinggi' => 'Tinggi badan',
            'berat' => 'Berat badan',
            'spo2' => 'SpO2',
            'gcs' => 'GCS',
            'kesadaran' => 'Kesadaran',
            'keluhan' => 'Subjek',
            'pemeriksaan' => 'Objek',
            'alergi' => 'Alergi',
            'penilaian' => 'Asesmen',
            'rtl' => 'Plan',
            'instruksi' => 'Instruksi',
            'evaluasi' => 'Evaluasi',
        ];
    }
}
