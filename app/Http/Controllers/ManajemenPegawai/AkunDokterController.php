<?php

namespace App\Http\Controllers\ManajemenPegawai;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManajemenPegawai\SimpanAkunLegacyRequest;
use App\Http\Requests\ManajemenPegawai\SimpanProfilDokterRequest;
use App\Services\ManajemenPegawai\ManajemenPegawaiService;
use App\Support\Feedback;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AkunDokterController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'account' => $request->string('account')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        return Inertia::render('ManajemenPegawai/AkunDokter', [
            'doctors' => Inertia::scroll(fn () => $this->listing($request, $filters)),
            'specialists' => fn () => DB::table('spesialis')
                ->select(['kd_sps', 'nm_sps'])
                ->orderBy('nm_sps')
                ->get(),
            'filters' => fn () => $filters,
        ]);
    }

    public function storeProfile(SimpanProfilDokterRequest $request, ManajemenPegawaiService $service): RedirectResponse
    {
        return Feedback::mutasi(
            fn (): mixed => $service->simpanProfilDokter($request->validated()),
            'Data dokter berhasil ditambahkan.',
            'Data dokter gagal ditambahkan.',
        );
    }

    public function updateProfile(
        SimpanProfilDokterRequest $request,
        ManajemenPegawaiService $service,
        string $doctor
    ): RedirectResponse {
        return Feedback::mutasi(
            fn (): mixed => $service->simpanProfilDokter($request->validated(), $doctor),
            'Data dokter berhasil diperbarui.',
            'Data dokter gagal diperbarui.',
        );
    }

    public function store(SimpanAkunLegacyRequest $request, ManajemenPegawaiService $service): RedirectResponse
    {
        $idUser = $request->string('id_user')->toString();

        return Feedback::mutasi(
            fn (): mixed => $service->simpanAkunDokter($idUser, $request->string('password')->toString()),
            'Akun dokter berhasil disimpan.',
            'Akun dokter gagal disimpan.',
        );
    }

    public function destroy(ManajemenPegawaiService $service, string $doctor): RedirectResponse
    {
        return Feedback::mutasi(
            fn (): mixed => $service->hapusAkunDokter($doctor),
            'Akun dokter berhasil dihapus.',
            'Akun dokter gagal dihapus.',
        );
    }

    /**
     * @param  array{search: string, account: string, status: string}  $filters
     */
    private function listing(Request $request, array $filters): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->when($filters['account'] === 'created', fn (Builder $query) => $query->whereNotNull('user.password'))
            ->when($filters['account'] === 'empty', fn (Builder $query) => $query->whereNull('user.password'))
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('dokter.status', $filters['status']))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('kd_dokter', 'like', "%{$search}%")
                        ->orWhere('nm_dokter', 'like', "%{$search}%")
                        ->orWhere('nm_sps', 'like', "%{$search}%")
                        ->orWhere('no_ijn_praktek', 'like', "%{$search}%");
                });
            })
            ->orderBy('kd_dokter')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();
    }

    public function updateStatus(Request $request, ManajemenPegawaiService $service, string $doctor): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['0', '1'])],
        ]);

        return Feedback::mutasi(
            fn (): mixed => $service->ubahStatusDokter($doctor, $data['status']),
            $data['status'] === '1'
                ? 'Dokter berhasil diaktifkan.'
                : 'Dokter berhasil dinonaktifkan.',
            $data['status'] === '1'
                ? 'Dokter gagal diaktifkan.'
                : 'Dokter gagal dinonaktifkan.',
        );
    }

    private function baseQuery(): Builder
    {
        return DB::table('dokter')
            ->selectRaw("
                dokter.kd_dokter,
                dokter.nm_dokter,
                dokter.jk,
                dokter.tmp_lahir,
                dokter.tgl_lahir,
                dokter.gol_drh,
                dokter.agama,
                dokter.almt_tgl,
                dokter.no_telp,
                dokter.stts_nikah,
                dokter.kd_sps,
                dokter.alumni,
                dokter.no_nip,
                dokter.no_ijn_praktek,
                dokter.status,
                pegawai.no_ktp,
                spesialis.nm_sps,
                AES_DECRYPT(user.password, 'windi') as password_decrypted
            ")
            ->join('spesialis', 'spesialis.kd_sps', '=', 'dokter.kd_sps')
            ->leftJoin('pegawai', 'pegawai.nik', '=', 'dokter.kd_dokter')
            ->leftJoin('user', DB::raw("AES_DECRYPT(user.id_user, 'nur')"), '=', 'dokter.kd_dokter');
    }
}
