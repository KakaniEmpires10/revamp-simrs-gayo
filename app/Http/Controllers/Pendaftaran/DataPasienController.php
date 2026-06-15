<?php

namespace App\Http\Controllers\Pendaftaran;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pendaftaran\GabungRekamMedisRequest;
use App\Http\Requests\Pendaftaran\SimpanDataPasienRequest;
use App\Http\Requests\Pendaftaran\UbahDataPasienRequest;
use App\Modules\Bpjs\Services\VClaimService;
use App\Services\Pendaftaran\PendaftaranService;
use App\Support\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DataPasienController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:40'],
            'jk' => ['nullable', 'in:L,P'],
            'create' => ['nullable', 'boolean'],
        ]);

        $search = $filters['search'] ?? '';
        $gender = $filters['jk'] ?? '';

        $patients = DB::table('pasien')
            ->select([
                'no_rkm_medis',
                'nm_pasien',
                'no_ktp',
                'no_peserta',
                'jk',
                'tmp_lahir',
                'tgl_lahir',
                'umur',
                'alamat',
                'no_tlp',
                'keluarga',
                'namakeluarga',
                'alamatpj',
                'kelurahanpj',
                'kecamatanpj',
                'kabupatenpj',
                'pasien.kd_pj',
                'penjab.png_jawab',
            ])
            ->leftJoin('penjab', 'penjab.kd_pj', '=', 'pasien.kd_pj')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('pasien.no_rkm_medis', 'like', "{$search}%")
                        ->orWhere('pasien.no_ktp', 'like', "{$search}%")
                        ->orWhere('pasien.no_peserta', 'like', "{$search}%")
                        ->orWhere('pasien.nm_pasien', 'like', "%{$search}%");
                });
            })
            ->when($gender !== '', fn ($query) => $query->where('pasien.jk', $gender))
            ->orderByDesc('pasien.no_rkm_medis')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Pendaftaran/DataPasien', [
            'patients' => $patients,
            'filters' => [
                'search' => $search,
                'jk' => $gender,
                'create' => (bool) ($filters['create'] ?? false),
            ],
        ]);
    }

    public function create(PendaftaranService $service): Response
    {
        return Inertia::render('Pendaftaran/TambahDataPasien', [
            'mode' => 'create',
            'patient' => null,
            'nextMedicalRecordNumber' => $service->nomorRekamMedisBerikutnya(),
            'references' => $service->referensiFormDataPasien(),
        ]);
    }

    public function store(
        SimpanDataPasienRequest $request,
        PendaftaranService $service
    ): RedirectResponse {
        return Feedback::mutasi(
            function () use ($request, $service): string {
                $patient = $service->simpanDataPasien($request->validated());

                return "Data pasien {$patient['no_rkm_medis']} berhasil disimpan.";
            },
            'Data pasien berhasil disimpan.',
            'Data pasien gagal disimpan.',
        );
    }

    public function edit(PendaftaranService $service, string $noRkmMedis): Response
    {
        $patient = $service->detailDataPasien($noRkmMedis);

        abort_if($patient === null, 404);

        return Inertia::render('Pendaftaran/TambahDataPasien', [
            'mode' => 'edit',
            'patient' => $patient,
            'nextMedicalRecordNumber' => null,
            'references' => $service->referensiFormDataPasien(),
        ]);
    }

    public function update(
        UbahDataPasienRequest $request,
        PendaftaranService $service,
        string $noRkmMedis
    ): RedirectResponse {
        return Feedback::mutasi(
            function () use ($request, $service, $noRkmMedis): string {
                $patient = $service->ubahDataPasien($noRkmMedis, $request->validated());

                return "Data pasien {$patient['no_rkm_medis']} berhasil diperbarui.";
            },
            'Data pasien berhasil diperbarui.',
            'Data pasien gagal diperbarui.',
        );
    }

    public function destroy(PendaftaranService $service, string $noRkmMedis): RedirectResponse
    {
        return Feedback::hasil(
            fn (): array => $service->hapusDataPasien($noRkmMedis),
            'Data pasien gagal dihapus.',
        );
    }

    public function mergeMedicalRecord(
        GabungRekamMedisRequest $request,
        PendaftaranService $service
    ): RedirectResponse {
        return Feedback::hasil(
            fn (): array => $service->gabungRekamMedis($request->validated()),
            'Gabung RM gagal diproses.',
        );
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:40'],
        ]);

        $query = $validated['query'];

        $patients = DB::table('pasien')
            ->select(['no_rkm_medis', 'nm_pasien', 'no_ktp', 'no_peserta'])
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('no_rkm_medis', 'like', "{$query}%")
                    ->orWhere('nm_pasien', 'like', "%{$query}%")
                    ->orWhere('no_ktp', 'like', "{$query}%")
                    ->orWhere('no_peserta', 'like', "{$query}%");
            })
            ->orderBy('no_rkm_medis')
            ->limit(20)
            ->get()
            ->map(fn (object $patient): array => [
                'value' => $patient->no_rkm_medis,
                'label' => "{$patient->no_rkm_medis} - {$patient->nm_pasien}",
                'description' => trim('NIK: '.($patient->no_ktp ?: '-').' | BPJS: '.($patient->no_peserta ?: '-')),
            ]);

        return response()->json(['data' => $patients]);
    }

    public function bpjsAutofill(Request $request, VClaimService $service): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:nik,card'],
            'identifier' => ['required', 'string', 'max:25'],
        ]);

        $result = $service->participant($validated['type'], preg_replace('/\D/', '', $validated['identifier']), now()->toDateString());
        $participant = $result['peserta'];

        return response()->json([
            'metadata' => $result['metadata'],
            'patient' => [
                'nm_pasien' => (string) Arr::get($participant, 'nama', ''),
                'no_ktp' => (string) Arr::get($participant, 'nik', ''),
                'no_peserta' => (string) Arr::get($participant, 'noKartu', ''),
                'jk' => substr((string) Arr::get($participant, 'sex', Arr::get($participant, 'kelamin', '')), 0, 1),
                'tgl_lahir' => (string) Arr::get($participant, 'tglLahir', ''),
            ],
        ]);
    }

    public function wilayahSearch(Request $request, PendaftaranService $service): JsonResponse
    {
        $validated = $request->validate([
            'jenis' => ['required', 'in:kelurahan,kecamatan,kabupaten,propinsi'],
            'query' => ['required', 'string', 'min:1', 'max:40'],
        ]);

        return response()->json([
            'data' => $service->referensiWilayah($validated['jenis'], $validated['query']),
        ]);
    }
}
