<?php

namespace App\Services\ManajemenUser;

use App\Models\ManajemenUser\GrupAuth;
use App\Models\ManajemenUser\IzinAuth;
use App\Models\ManajemenUser\UserAuth;
use Illuminate\Support\Facades\DB;

class ManajemenUserService
{
    /**
     * @param  array{name:string,alias:string,keterangan:string}  $data
     */
    public function simpanGrup(array $data): void
    {
        GrupAuth::query()->create([
            'name' => $data['name'],
            'alias' => $data['alias'],
            'keterangan' => $data['keterangan'],
        ]);
    }

    /**
     * @param  array{name:string,alias:string,keterangan:string}  $data
     */
    public function ubahGrup(GrupAuth $grup, array $data): void
    {
        DB::transaction(function () use ($grup, $data): void {
            $aliasLama = $grup->alias;
            $aliasBaru = $data['alias'];

            $grup->update([
                'name' => $data['name'],
                'alias' => $aliasBaru,
                'keterangan' => $data['keterangan'],
            ]);

            if ($aliasLama !== $aliasBaru) {
                IzinAuth::query()
                    ->where('alias_group', $aliasLama)
                    ->update(['alias_group' => $aliasBaru]);
            }
        });
    }

    public function hapusGrup(GrupAuth $grup): bool
    {
        if ($grup->permissions()->exists()) {
            return false;
        }

        $grup->delete();

        return true;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function ubahIzinGrup(GrupAuth $grup, array $permissions): void
    {
        $permissions = collect($permissions)
            ->merge($this->requiredCompanionPermissions($permissions))
            ->unique()
            ->values();

        DB::transaction(function () use ($grup, $permissions): void {
            IzinAuth::query()
                ->where('alias_group', $grup->alias)
                ->delete();

            if ($permissions->isNotEmpty()) {
                IzinAuth::query()->insert(
                    $permissions
                        ->map(fn (string $url): array => [
                            'alias_group' => $grup->alias,
                            'url' => $url,
                        ])
                        ->all()
                );
            }
        });

        $this->flushAksesUserCache($grup->alias);
    }

    public function ubahAksesUser(object $user, string $aliasGroup): void
    {
        UserAuth::query()->updateOrCreate(
            ['id' => $user->id_user_decrypted],
            [
                'id_user' => $user->id_user,
                'alias_group' => $aliasGroup,
            ]
        );

        cache()->forget("user:{$user->id_user_decrypted}");
    }

    public function hapusAksesUser(string $idUser): void
    {
        UserAuth::query()
            ->where('id', $idUser)
            ->delete();

        cache()->forget("user:{$idUser}");
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    private function requiredCompanionPermissions(array $permissions): array
    {
        $companions = [
            '/tindakan-rekam-medis/ralan' => '/riwayat-pasien',
            '/tindakan-rekam-medis/rujukan-poli' => '/riwayat-pasien',
            '/tindakan-rekam-medis/ranap' => '/riwayat-pasien',
        ];

        return collect($companions)
            ->filter(fn (string $target, string $source): bool => in_array($source, $permissions, true))
            ->values()
            ->all();
    }

    private function flushAksesUserCache(string $aliasGroup): void
    {
        DB::table('uxui_auth_users')
            ->where('alias_group', $aliasGroup)
            ->selectRaw("AES_DECRYPT(id_user, 'nur') as id_user")
            ->pluck('id_user')
            ->filter()
            ->each(fn (string $idUser): bool => cache()->forget("user:{$idUser}"));
    }
}
