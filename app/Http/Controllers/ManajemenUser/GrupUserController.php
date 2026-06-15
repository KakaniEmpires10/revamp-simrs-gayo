<?php

namespace App\Http\Controllers\ManajemenUser;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManajemenUser\SimpanGrupAuthRequest;
use App\Http\Requests\ManajemenUser\UbahGrupAuthRequest;
use App\Models\ManajemenUser\GrupAuth;
use App\Models\ManajemenUser\IzinAuth;
use App\Services\ManajemenUser\ManajemenUserService;
use App\Support\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GrupUserController extends Controller
{
    public function index(): Response
    {
        $groups = GrupAuth::query()
            ->select(['id', 'name', 'alias', 'keterangan', 'created'])
            ->selectSub(
                IzinAuth::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('alias_group', 'uxui_auth_group.alias'),
                'permissions_count'
            )
            ->selectSub(
                DB::table('uxui_auth_users')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('alias_group', 'uxui_auth_group.alias'),
                'users_count'
            )
            ->orderByDesc('id')
            ->get();

        return Inertia::render('ManajemenUser/GrupUser', [
            'groups' => $groups,
        ]);
    }

    public function store(SimpanGrupAuthRequest $request, ManajemenUserService $service): RedirectResponse
    {
        return Feedback::mutasi(
            fn (): mixed => $service->simpanGrup([
                'name' => $request->string('name')->squish()->toString(),
                'alias' => $request->string('alias')->toString(),
                'keterangan' => $request->string('keterangan')->squish()->toString(),
            ]),
            'Level akses berhasil ditambahkan.',
            'Level akses gagal ditambahkan.',
        );
    }

    public function update(
        UbahGrupAuthRequest $request,
        ManajemenUserService $service,
        GrupAuth $authGroup
    ): RedirectResponse {
        return Feedback::mutasi(
            fn (): mixed => $service->ubahGrup($authGroup, [
                'name' => $request->string('name')->squish()->toString(),
                'alias' => $request->string('alias')->toString(),
                'keterangan' => $request->string('keterangan')->squish()->toString(),
            ]),
            'Level akses berhasil diperbarui.',
            'Level akses gagal diperbarui.',
        );
    }

    public function destroy(ManajemenUserService $service, GrupAuth $authGroup): RedirectResponse
    {
        return Feedback::mutasi(
            function () use ($service, $authGroup): void {
                if (! $service->hapusGrup($authGroup)) {
                    throw ValidationException::withMessages([
                        'auth_group' => 'Level akses sudah memiliki permission dan belum dapat dihapus.',
                    ]);
                }
            },
            'Level akses berhasil dihapus.',
            'Level akses gagal dihapus.',
        );
    }
}
