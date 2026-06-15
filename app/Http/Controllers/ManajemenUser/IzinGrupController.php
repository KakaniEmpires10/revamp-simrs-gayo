<?php

namespace App\Http\Controllers\ManajemenUser;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManajemenUser\UbahIzinGrupRequest;
use App\Models\ManajemenUser\GrupAuth;
use App\Models\ManajemenUser\IzinAuth;
use App\Models\ManajemenUser\RuteAuth;
use App\Services\ManajemenUser\ManajemenUserService;
use App\Support\Feedback;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IzinGrupController extends Controller
{
    public function edit(GrupAuth $authGroup): Response
    {
        $routeGroups = RuteAuth::query()
            ->select(['id', 'title', 'url', 'type'])
            ->orderBy('id')
            ->get()
            ->groupBy('title')
            ->map(fn ($routes, string $title): array => [
                'title' => $title,
                'routes' => $routes->values(),
            ])
            ->values();

        $checkedPermissions = IzinAuth::query()
            ->where('alias_group', $authGroup->alias)
            ->pluck('url')
            ->all();

        return Inertia::render('ManajemenUser/IzinGrup', [
            'group' => $authGroup->only(['id', 'name', 'alias', 'keterangan']),
            'routeGroups' => $routeGroups,
            'checkedPermissions' => $checkedPermissions,
        ]);
    }

    public function update(
        UbahIzinGrupRequest $request,
        ManajemenUserService $service,
        GrupAuth $authGroup
    ): RedirectResponse {
        return Feedback::mutasi(
            fn (): mixed => $service->ubahIzinGrup($authGroup, $request->validated('permissions', [])),
            'Permission level akses berhasil diperbarui.',
            'Permission level akses gagal diperbarui.',
        );
    }
}
