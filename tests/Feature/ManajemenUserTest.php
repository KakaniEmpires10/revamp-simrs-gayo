<?php

use App\Models\ManajemenUser\GrupAuth;

test('user management routes require authentication', function (string $routeName) {
    $response = $this->get(route($routeName));

    $response->assertRedirect(route('login'));
})->with([
    'user group' => 'manajemen-user.grup-user.index',
    'user access' => 'manajemen-user.akses-user.index',
    'doctor accounts' => 'manajemen-pegawai.akun-dokter.index',
    'staff accounts' => 'manajemen-pegawai.akun-petugas.index',
    'practice schedules' => 'manajemen-pegawai.jadwal-praktek.index',
]);

test('employee management write routes require authentication', function (string $method, string $routeName, array $parameters = []) {
    $response = $this->{$method}(route($routeName, $parameters));

    $response->assertRedirect(route('login'));
})->with([
    'create doctor profile' => ['post', 'manajemen-pegawai.akun-dokter.profil.store'],
    'update doctor profile' => ['patch', 'manajemen-pegawai.akun-dokter.profil.update', ['doctor' => 'D001']],
    'update doctor status' => ['patch', 'manajemen-pegawai.akun-dokter.status.update', ['doctor' => 'D001']],
    'create staff profile' => ['post', 'manajemen-pegawai.akun-petugas.profil.store'],
    'update staff profile' => ['patch', 'manajemen-pegawai.akun-petugas.profil.update', ['staff' => 'P001']],
    'update staff status' => ['patch', 'manajemen-pegawai.akun-petugas.status.update', ['staff' => 'P001']],
]);

test('legacy user group alias is generated from the group name', function () {
    expect(GrupAuth::aliasFromName(' Petugas   Pendaftaran '))
        ->toBe('group_petugas_pendaftaran');
});
