<?php

namespace App\Http\Controllers\ManajemenUser;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManajemenUser\UbahAksesUserRequest;
use App\Models\ManajemenUser\GrupAuth;
use App\Services\ManajemenUser\ManajemenUserService;
use App\Support\Feedback;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AksesUserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'alias_group' => $request->string('alias_group')->toString(),
            'status' => $request->string('status')->toString(),
            'search' => $request->string('search')->trim()->toString(),
        ];

        return Inertia::render('ManajemenUser/AksesUser', [
            'users' => Inertia::scroll(fn () => $this->userAccessListing($request, $filters)),
            'groups' => fn () => GrupAuth::query()
                ->select(['id', 'name', 'alias'])
                ->orderBy('name')
                ->get(),
            'jenisAkun' => fn () => [
                ['value' => 'dokter', 'label' => 'Dokter'],
                ['value' => 'petugas', 'label' => 'Petugas'],
            ],
            'filters' => fn () => $filters,
        ]);
    }

    public function update(
        UbahAksesUserRequest $request,
        ManajemenUserService $service,
        string $idUser
    ): RedirectResponse {
        $user = $this->baseAksesUserQuery()
            ->where('id_user_decrypted', $idUser)
            ->firstOrFail();

        return Feedback::mutasi(
            fn (): mixed => $service->ubahAksesUser($user, $request->string('alias_group')->toString()),
            'Level akses pengguna berhasil diperbarui.',
            'Level akses pengguna gagal diperbarui.',
        );
    }

    public function destroy(ManajemenUserService $service, string $idUser): RedirectResponse
    {
        return Feedback::mutasi(
            fn (): mixed => $service->hapusAksesUser($idUser),
            'Level akses pengguna berhasil dihapus.',
            'Level akses pengguna gagal dihapus.',
        );
    }

    /**
     * @param  array{alias_group: string, status: string, search: string}  $filters
     */
    private function userAccessListing(Request $request, array $filters): LengthAwarePaginator
    {
        return $this->baseAksesUserQuery()
            ->when($filters['alias_group'] !== '', fn ($query) => $query->where('alias_group', $filters['alias_group']))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('id_user_decrypted', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->orderBy('id_user_decrypted')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();
    }

    private function baseAksesUserQuery(): Builder
    {
        return DB::query()->fromSub(
            DB::table('user')
                ->selectRaw("
                    AES_DECRYPT(user.id_user, 'nur') as id_user_decrypted,
                    user.id_user,
                    AES_DECRYPT(user.password, 'windi') as password_decrypted,
                    IF(dokter.kd_dokter IS NOT NULL, dokter.nm_dokter, IF(petugas.nip IS NOT NULL, petugas.nama, NULL)) as nama,
                    IF(dokter.kd_dokter IS NOT NULL, 'dokter', IF(petugas.nip IS NOT NULL, 'petugas', NULL)) as status,
                    uxui_auth_users.alias_group,
                    uxui_auth_group.name as group_name
                ")
                ->leftJoin('dokter', 'dokter.kd_dokter', '=', DB::raw("AES_DECRYPT(user.id_user, 'nur')"))
                ->leftJoin('petugas', 'petugas.nip', '=', DB::raw("AES_DECRYPT(user.id_user, 'nur')"))
                ->leftJoin('uxui_auth_users', 'uxui_auth_users.id', '=', DB::raw("AES_DECRYPT(user.id_user, 'nur')"))
                ->leftJoin('uxui_auth_group', 'uxui_auth_group.alias', '=', 'uxui_auth_users.alias_group'),
            'user_access'
        );
    }
}
