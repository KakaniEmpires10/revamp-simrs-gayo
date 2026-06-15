<?php

namespace App\Http\Controllers\ManajemenPegawai;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManajemenPegawai\SimpanAkunLegacyRequest;
use App\Http\Requests\ManajemenPegawai\SimpanProfilPetugasRequest;
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

class AkunPetugasController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'account' => $request->string('account')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        return Inertia::render('ManajemenPegawai/AkunPetugas', [
            'staff' => Inertia::scroll(fn () => $this->listing($request, $filters)),
            'positions' => fn () => DB::table('jabatan')
                ->select(['kd_jbtn', 'nm_jbtn'])
                ->orderBy('nm_jbtn')
                ->get(),
            'filters' => fn () => $filters,
        ]);
    }

    public function storeProfile(SimpanProfilPetugasRequest $request, ManajemenPegawaiService $service): RedirectResponse
    {
        return Feedback::mutasi(
            fn (): mixed => $service->simpanProfilPetugas($request->validated()),
            'Data petugas berhasil ditambahkan.',
            'Data petugas gagal ditambahkan.',
        );
    }

    public function updateProfile(
        SimpanProfilPetugasRequest $request,
        ManajemenPegawaiService $service,
        string $staff
    ): RedirectResponse {
        return Feedback::mutasi(
            fn (): mixed => $service->simpanProfilPetugas($request->validated(), $staff),
            'Data petugas berhasil diperbarui.',
            'Data petugas gagal diperbarui.',
        );
    }

    public function store(SimpanAkunLegacyRequest $request, ManajemenPegawaiService $service): RedirectResponse
    {
        $idUser = $request->string('id_user')->toString();

        return Feedback::mutasi(
            fn (): mixed => $service->simpanAkunPetugas($idUser, $request->string('password')->toString()),
            'Akun petugas berhasil disimpan.',
            'Akun petugas gagal disimpan.',
        );
    }

    public function destroy(ManajemenPegawaiService $service, string $staff): RedirectResponse
    {
        return Feedback::mutasi(
            fn (): mixed => $service->hapusAkunPetugas($staff),
            'Akun petugas berhasil dihapus.',
            'Akun petugas gagal dihapus.',
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
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('petugas.status', $filters['status']))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('petugas.nip', 'like', "%{$search}%")
                        ->orWhere('petugas.nama', 'like', "%{$search}%")
                        ->orWhere('jabatan.nm_jbtn', 'like', "%{$search}%");
                });
            })
            ->orderBy('petugas.nip')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();
    }

    public function updateStatus(Request $request, ManajemenPegawaiService $service, string $staff): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['0', '1'])],
        ]);

        return Feedback::mutasi(
            fn (): mixed => $service->ubahStatusPetugas($staff, $data['status']),
            $data['status'] === '1'
                ? 'Petugas berhasil diaktifkan.'
                : 'Petugas berhasil dinonaktifkan.',
            $data['status'] === '1'
                ? 'Petugas gagal diaktifkan.'
                : 'Petugas gagal dinonaktifkan.',
        );
    }

    private function baseQuery(): Builder
    {
        return DB::table('petugas')
            ->selectRaw("
                petugas.nip,
                petugas.nama,
                petugas.jk,
                petugas.tmp_lahir,
                petugas.tgl_lahir,
                petugas.gol_darah,
                petugas.agama,
                petugas.stts_nikah,
                petugas.alamat,
                petugas.kd_jbtn,
                petugas.no_telp,
                petugas.status,
                pegawai.no_ktp,
                jabatan.nm_jbtn,
                AES_DECRYPT(user.password, 'windi') as password_decrypted
            ")
            ->join('jabatan', 'jabatan.kd_jbtn', '=', 'petugas.kd_jbtn')
            ->leftJoin('pegawai', 'pegawai.nik', '=', 'petugas.nip')
            ->leftJoin('user', DB::raw("AES_DECRYPT(user.id_user, 'nur')"), '=', 'petugas.nip');
    }
}
