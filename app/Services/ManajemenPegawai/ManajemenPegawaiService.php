<?php

namespace App\Services\ManajemenPegawai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ManajemenPegawaiService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function simpanProfilDokter(array $data, ?string $dokterLama = null): void
    {
        $kodeDokter = (string) $data['kd_dokter'];
        $dokterLama ??= $kodeDokter;

        if ($dokterLama !== $kodeDokter) {
            throw ValidationException::withMessages([
                'kd_dokter' => 'Kode dokter tidak dapat diubah setelah dibuat.',
            ]);
        }

        $jenisKelaminPegawai = $data['jk'] === 'P' ? 'Wanita' : 'Pria';

        DB::transaction(function () use ($data, $kodeDokter, $dokterLama, $jenisKelaminPegawai): void {
            $this->simpanPegawai(
                nik: $kodeDokter,
                nikLama: $dokterLama,
                nama: (string) $data['nm_dokter'],
                jenisKelamin: $jenisKelaminPegawai,
                tempatLahir: $data['tmp_lahir'] ?? null,
                tanggalLahir: $data['tgl_lahir'] ?: today()->toDateString(),
                alamat: $data['almt_tgl'] ?? null,
                noKtp: $data['no_ktp'] ?? null,
            );

            DB::table('dokter')->updateOrInsert(
                ['kd_dokter' => $dokterLama],
                [
                    'kd_dokter' => $kodeDokter,
                    'nm_dokter' => $data['nm_dokter'],
                    'jk' => $data['jk'],
                    'tmp_lahir' => $data['tmp_lahir'] ?: null,
                    'tgl_lahir' => $data['tgl_lahir'] ?: null,
                    'gol_drh' => $data['gol_drh'] ?: '-',
                    'agama' => $data['agama'] ?: null,
                    'almt_tgl' => $data['almt_tgl'] ?: null,
                    'no_telp' => $data['no_telp'] ?: null,
                    'stts_nikah' => $data['stts_nikah'] ?: null,
                    'kd_sps' => $data['kd_sps'],
                    'alumni' => $data['alumni'] ?: null,
                    'no_nip' => $data['no_nip'] ?: null,
                    'no_ijn_praktek' => $data['no_ijn_praktek'] ?: null,
                    'status' => $data['status'],
                ]
            );
        });
    }

    public function simpanAkunDokter(string $idUser, string $password): void
    {
        $exists = DB::table('dokter')
            ->where('kd_dokter', $idUser)
            ->where('status', '1')
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'id_user' => 'Dokter aktif tidak ditemukan.',
            ]);
        }

        $this->simpanAkunLegacy($idUser, $password);
    }

    public function hapusAkunDokter(string $idUser): void
    {
        $this->hapusAkunLegacy($idUser);
    }

    public function ubahStatusDokter(string $kodeDokter, string $status): void
    {
        $exists = DB::table('dokter')
            ->where('kd_dokter', $kodeDokter)
            ->exists();

        if (! $exists) {
            abort(404);
        }

        DB::table('dokter')
            ->where('kd_dokter', $kodeDokter)
            ->update(['status' => $status]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function simpanProfilPetugas(array $data, ?string $petugasLama = null): void
    {
        $nip = (string) $data['nip'];
        $petugasLama ??= $nip;

        if ($petugasLama !== $nip) {
            throw ValidationException::withMessages([
                'nip' => 'NIP petugas tidak dapat diubah setelah dibuat.',
            ]);
        }

        $jenisKelaminPegawai = $data['jk'] === 'P' ? 'Wanita' : 'Pria';

        DB::transaction(function () use ($data, $nip, $petugasLama, $jenisKelaminPegawai): void {
            $this->simpanPegawai(
                nik: $nip,
                nikLama: $petugasLama,
                nama: (string) $data['nama'],
                jenisKelamin: $jenisKelaminPegawai,
                tempatLahir: $data['tmp_lahir'] ?? null,
                tanggalLahir: $data['tgl_lahir'] ?: today()->toDateString(),
                alamat: $data['alamat'] ?? null,
                noKtp: $data['no_ktp'] ?? null,
            );

            DB::table('petugas')->updateOrInsert(
                ['nip' => $petugasLama],
                [
                    'nip' => $nip,
                    'nama' => $data['nama'],
                    'jk' => $data['jk'],
                    'tmp_lahir' => $data['tmp_lahir'] ?: null,
                    'tgl_lahir' => $data['tgl_lahir'] ?: null,
                    'gol_darah' => $data['gol_darah'] ?: '-',
                    'agama' => $data['agama'] ?: null,
                    'stts_nikah' => $data['stts_nikah'] ?: null,
                    'alamat' => $data['alamat'] ?: null,
                    'kd_jbtn' => $data['kd_jbtn'],
                    'no_telp' => $data['no_telp'] ?: null,
                    'status' => $data['status'],
                ]
            );
        });
    }

    public function simpanAkunPetugas(string $idUser, string $password): void
    {
        $exists = DB::table('petugas')
            ->where('nip', $idUser)
            ->where('status', '1')
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'id_user' => 'Petugas aktif tidak ditemukan.',
            ]);
        }

        $this->simpanAkunLegacy($idUser, $password);
    }

    public function hapusAkunPetugas(string $idUser): void
    {
        $this->hapusAkunLegacy($idUser);
    }

    public function ubahStatusPetugas(string $nip, string $status): void
    {
        $exists = DB::table('petugas')
            ->where('nip', $nip)
            ->exists();

        if (! $exists) {
            abort(404);
        }

        DB::table('petugas')
            ->where('nip', $nip)
            ->update(['status' => $status]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function simpanJadwalPraktek(array $data): void
    {
        DB::table('jadwal')->updateOrInsert(
            [
                'kd_dokter' => $data['kd_dokter'],
                'hari_kerja' => $data['hari_kerja'],
                'jam_mulai' => $this->timeValue((string) $data['jam_mulai']),
            ],
            [
                'jam_selesai' => $this->nullableTimeValue((string) ($data['jam_selesai'] ?? '')),
                'kd_poli' => $data['kd_poli'],
                'kuota' => (int) ($data['kuota'] ?? 0) ?: null,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ubahJadwalPraktek(array $data, string $dokter, string $hari, string $jamMulai): void
    {
        DB::transaction(function () use ($data, $dokter, $hari, $jamMulai): void {
            $this->hapusJadwalPraktek($dokter, $hari, $jamMulai);

            DB::table('jadwal')->insert([
                'kd_dokter' => $data['kd_dokter'],
                'hari_kerja' => $data['hari_kerja'],
                'jam_mulai' => $this->timeValue((string) $data['jam_mulai']),
                'jam_selesai' => $this->nullableTimeValue((string) ($data['jam_selesai'] ?? '')),
                'kd_poli' => $data['kd_poli'],
                'kuota' => (int) ($data['kuota'] ?? 0) ?: null,
            ]);
        });
    }

    public function hapusJadwalPraktek(string $dokter, string $hari, string $jamMulai): void
    {
        DB::table('jadwal')
            ->where('kd_dokter', $dokter)
            ->where('hari_kerja', $hari)
            ->where('jam_mulai', $this->timeValue($jamMulai))
            ->delete();
    }

    private function simpanAkunLegacy(string $idUser, string $password): void
    {
        $exists = DB::table('user')
            ->whereRaw("id_user = AES_ENCRYPT(?, 'nur')", [$idUser])
            ->exists();

        if ($exists) {
            DB::table('user')
                ->whereRaw("id_user = AES_ENCRYPT(?, 'nur')", [$idUser])
                ->update([
                    'password' => DB::raw('AES_ENCRYPT('.$this->quote($password).", 'windi')"),
                ]);

            return;
        }

        $payload = $this->legacyUserDefaults();
        $payload['id_user'] = DB::raw('AES_ENCRYPT('.$this->quote($idUser).", 'nur')");
        $payload['password'] = DB::raw('AES_ENCRYPT('.$this->quote($password).", 'windi')");

        DB::table('user')->insert($payload);
    }

    private function hapusAkunLegacy(string $idUser): void
    {
        DB::table('user')
            ->whereRaw("id_user = AES_ENCRYPT(?, 'nur')", [$idUser])
            ->delete();
    }

    private function simpanPegawai(
        string $nik,
        string $nikLama,
        string $nama,
        string $jenisKelamin,
        mixed $tempatLahir,
        mixed $tanggalLahir,
        mixed $alamat,
        mixed $noKtp,
    ): void {
        $existingPegawai = DB::table('pegawai')
            ->where('nik', $nikLama)
            ->first();

        $pegawaiPayload = [
            ...$this->pegawaiDefaults(),
            'nik' => $nik,
            'nama' => $nama,
            'jk' => $jenisKelamin,
            'tmp_lahir' => $this->filledOrDash($tempatLahir),
            'tgl_lahir' => $tanggalLahir,
            'alamat' => $this->filledOrDash($alamat),
            'no_ktp' => $this->filledOrDash($noKtp),
        ];

        if ($existingPegawai) {
            DB::table('pegawai')
                ->where('nik', $nikLama)
                ->update($pegawaiPayload);

            return;
        }

        DB::table('pegawai')->insert($pegawaiPayload);
    }

    /**
     * @return array<string, string>
     */
    private function legacyUserDefaults(): array
    {
        return collect(Schema::getColumns('user'))
            ->filter(fn (array $column): bool => str_starts_with((string) $column['type'], 'enum('))
            ->mapWithKeys(fn (array $column): array => [$column['name'] => 'false'])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function pegawaiDefaults(): array
    {
        return [
            'jbtn' => '-',
            'jnj_jabatan' => '-',
            'kode_kelompok' => '-',
            'kode_resiko' => '-',
            'kode_emergency' => '-',
            'departemen' => '-',
            'bidang' => '-',
            'stts_wp' => '-',
            'stts_kerja' => '-',
            'npwp' => '-',
            'pendidikan' => '-',
            'gapok' => 0,
            'kota' => '-',
            'mulai_kerja' => today()->toDateString(),
            'ms_kerja' => '<1',
            'indexins' => '-',
            'bpd' => 'T',
            'rekening' => '-',
            'stts_aktif' => 'AKTIF',
            'wajibmasuk' => 0,
            'pengurang' => 0,
            'indek' => 0,
            'mulai_kontrak' => today()->toDateString(),
            'cuti_diambil' => 0,
            'dankes' => 0,
            'photo' => 'pages/pegawai/photo/',
        ];
    }

    private function filledOrDash(mixed $value): string
    {
        return filled($value) ? (string) $value : '-';
    }

    private function quote(string $value): string
    {
        return DB::connection()->getPdo()->quote($value);
    }

    private function timeValue(string $value): string
    {
        return strlen($value) === 5 ? "{$value}:00" : $value;
    }

    private function nullableTimeValue(string $value): ?string
    {
        return $value === '' ? null : $this->timeValue($value);
    }
}
