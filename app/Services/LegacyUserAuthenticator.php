<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class LegacyUserAuthenticator
{
    public function authenticate(string $idUser, string $password): ?User
    {
        $idUser = trim($idUser);

        if ($idUser === '' || $password === '') {
            return null;
        }

        try {
            $legacyUser = DB::table('user')
                ->select('id_user')
                ->whereRaw("id_user = AES_ENCRYPT(?,'nur')", [$idUser])
                ->whereRaw("password = AES_ENCRYPT(?,'windi')", [$password])
                ->first();

            if ($legacyUser !== null) {
                return $this->syncUxuiUser($idUser, $legacyUser->id_user, $this->resolveLegacyUserProfile($idUser));
            }

            $admin = DB::table('admin')
                ->select('usere as id_user')
                ->whereRaw("usere = AES_ENCRYPT(?,'nur')", [$idUser])
                ->whereRaw("passworde = AES_ENCRYPT(?,'windi')", [$password])
                ->first();

            if ($admin !== null) {
                return $this->syncUxuiUser($idUser, $admin->id_user, [
                    'name' => 'Admin',
                    'type_user' => 'admin',
                ]);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @return array{name: string, type_user: string}|null
     */
    private function resolveLegacyUserProfile(string $idUser): ?array
    {
        $doctor = DB::table('dokter')
            ->select('nm_dokter')
            ->where('kd_dokter', $idUser)
            ->where('status', '1')
            ->first();

        if ($doctor !== null) {
            return [
                'name' => (string) $doctor->nm_dokter,
                'type_user' => 'dokter',
            ];
        }

        $staff = DB::table('petugas')
            ->select('nama')
            ->where('nip', $idUser)
            ->where('status', '1')
            ->first();

        if ($staff !== null) {
            return [
                'name' => (string) $staff->nama,
                'type_user' => 'petugas',
            ];
        }

        return null;
    }

    /**
     * @param  array{name: string, type_user: string}|null  $profile
     */
    private function syncUxuiUser(string $idUser, string $encryptedIdUser, ?array $profile): ?User
    {
        if ($profile === null) {
            return null;
        }

        return User::query()->updateOrCreate(
            ['id' => $idUser],
            [
                'id_user' => $encryptedIdUser,
                'name' => $profile['name'],
                'type_user' => $profile['type_user'],
            ],
        );
    }
}
