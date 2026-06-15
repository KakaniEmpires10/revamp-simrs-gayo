<?php

namespace App\Services\Rme;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PemeriksaanRmeService
{
    private const KODE_POLI_IGD = 'IGDK';

    /**
     * @return array<string, mixed>|null
     */
    public function konteksPasien(string $noRawat, ?string $asal = null): ?array
    {
        $asal = $this->normalisasiAsal($asal);

        $kamarTerakhir = DB::table('kamar_inap as kamar_urut')
            ->select([
                'kamar_urut.no_rawat',
                DB::raw("MAX(CONCAT(kamar_urut.tgl_masuk, ' ', kamar_urut.jam_masuk)) as waktu_masuk_terakhir"),
            ])
            ->groupBy('kamar_urut.no_rawat');

        $patient = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('dokter', 'reg_periksa.kd_dokter', '=', 'dokter.kd_dokter')
            ->join('poliklinik', 'reg_periksa.kd_poli', '=', 'poliklinik.kd_poli')
            ->join('penjab', 'reg_periksa.kd_pj', '=', 'penjab.kd_pj')
            ->leftJoin('bridging_sep as sep_ralan', function ($join): void {
                $join->on('sep_ralan.no_rawat', '=', 'reg_periksa.no_rawat')
                    ->where('sep_ralan.jnspelayanan', '2');
            })
            ->leftJoin('bridging_sep as sep_ranap', function ($join): void {
                $join->on('sep_ranap.no_rawat', '=', 'reg_periksa.no_rawat')
                    ->where('sep_ranap.jnspelayanan', '1');
            })
            ->leftJoinSub($kamarTerakhir, 'kamar_terakhir', function ($join): void {
                $join->on('kamar_terakhir.no_rawat', '=', 'reg_periksa.no_rawat');
            })
            ->leftJoin('kamar_inap', function ($join): void {
                $join->on('kamar_inap.no_rawat', '=', 'kamar_terakhir.no_rawat')
                    ->whereRaw("CONCAT(kamar_inap.tgl_masuk, ' ', kamar_inap.jam_masuk) = kamar_terakhir.waktu_masuk_terakhir");
            })
            ->leftJoin('kamar', 'kamar_inap.kd_kamar', '=', 'kamar.kd_kamar')
            ->leftJoin('bangsal', 'kamar.kd_bangsal', '=', 'bangsal.kd_bangsal')
            ->where('reg_periksa.no_rawat', $noRawat)
            ->select([
                'reg_periksa.no_rawat',
                'reg_periksa.no_reg',
                'reg_periksa.no_rkm_medis',
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.kd_poli',
                'reg_periksa.kd_pj',
                'reg_periksa.stts',
                'reg_periksa.stts_daftar',
                'reg_periksa.status_lanjut',
                'pasien.nm_pasien',
                'pasien.jk',
                'pasien.tgl_lahir',
                'pasien.no_ktp',
                'pasien.no_peserta',
                'dokter.kd_dokter',
                'dokter.nm_dokter',
                'poliklinik.nm_poli',
                'penjab.png_jawab',
                'kamar.kelas',
                'kamar.kd_kamar',
                'bangsal.nm_bangsal',
                DB::raw('COALESCE(sep_ranap.no_sep, sep_ralan.no_sep) as no_sep'),
                DB::raw('COALESCE(sep_ranap.klsrawat, sep_ralan.klsrawat) as kelas_sep'),
                DB::raw("CONCAT(reg_periksa.umurdaftar, ' ', reg_periksa.sttsumur) as umur_registrasi"),
            ])
            ->first();

        if ($patient === null) {
            return null;
        }

        $resolvedAsal = $asal ?: $this->asalDariRegistrasi((string) $patient->status_lanjut, (string) $patient->kd_poli);

        return [
            'no_rawat' => (string) $patient->no_rawat,
            'no_reg' => (string) $patient->no_reg,
            'no_rkm_medis' => (string) $patient->no_rkm_medis,
            'tgl_registrasi' => (string) $patient->tgl_registrasi,
            'jam_reg' => (string) $patient->jam_reg,
            'nm_pasien' => (string) $patient->nm_pasien,
            'jk' => (string) $patient->jk,
            'tgl_lahir' => $patient->tgl_lahir ? (string) $patient->tgl_lahir : null,
            'no_ktp' => $patient->no_ktp ? (string) $patient->no_ktp : null,
            'no_peserta' => $patient->no_peserta ? (string) $patient->no_peserta : null,
            'kd_dokter' => (string) $patient->kd_dokter,
            'nm_dokter' => (string) $patient->nm_dokter,
            'kd_poli' => (string) $patient->kd_poli,
            'nm_poli' => (string) $patient->nm_poli,
            'kd_pj' => (string) $patient->kd_pj,
            'png_jawab' => (string) $patient->png_jawab,
            'stts' => (string) $patient->stts,
            'stts_daftar' => (string) $patient->stts_daftar,
            'status_lanjut' => (string) $patient->status_lanjut,
            'kelas' => $patient->kelas ? (string) $patient->kelas : null,
            'kelas_sep' => $patient->kelas_sep ? (string) $patient->kelas_sep : null,
            'kd_kamar' => $patient->kd_kamar ? (string) $patient->kd_kamar : null,
            'nm_bangsal' => $patient->nm_bangsal ? (string) $patient->nm_bangsal : null,
            'no_sep' => $patient->no_sep ? (string) $patient->no_sep : null,
            'umur_registrasi' => $patient->umur_registrasi ? (string) $patient->umur_registrasi : null,
            'asal' => $resolvedAsal,
            'type_akses' => $this->typeAkses($resolvedAsal),
        ];
    }

    /**
     * @return list<array{key:string,label:string,description:string,icon:string,href:string,children?:list<array{key:string,label:string,description:string,icon:string,href:string}>}>
     */
    public function menuPemeriksaan(string $noRawat, ?string $asal = null): array
    {
        $asal = $this->normalisasiAsal($asal);

        return collect(config('pemeriksaan.menu', []))
            ->map(function (array $menu) use ($noRawat, $asal): array {
                return [
                    'key' => (string) $menu['key'],
                    'label' => (string) $menu['label'],
                    'description' => (string) $menu['description'],
                    'icon' => (string) $menu['icon'],
                    'href' => $this->hrefMenuPemeriksaan($menu, $noRawat, $asal),
                    'children' => collect($menu['children'] ?? [])
                        ->map(fn (array $child): array => [
                            'key' => (string) $child['key'],
                            'label' => (string) $child['label'],
                            'description' => (string) $child['description'],
                            'icon' => (string) $child['icon'],
                            'href' => $this->hrefMenuPemeriksaan($child, $noRawat, $asal),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function defaultCppt(User $user, string $noRawat): array
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $pengisi = $this->pengisiSaatIni($user);

        return [
            'no_rawat' => $noRawat,
            'tgl_perawatan' => $now->toDateString(),
            'jam_rawat' => $now->format('H:i'),
            'nip' => $pengisi['value'],
            'nama_pengisi' => $pengisi['label'],
            'jenis_pengisi' => $pengisi['type'],
            'kesadaran' => 'Compos Mentis',
        ];
    }

    /**
     * @return array{value:string,label:string,type:string}
     */
    public function pengisiSaatIni(User $user): array
    {
        $idUser = (string) $user->getAuthIdentifier();
        $doctor = DB::table('dokter')
            ->where('kd_dokter', $idUser)
            ->first(['kd_dokter', 'nm_dokter']);

        if ($doctor !== null) {
            return [
                'value' => (string) $doctor->kd_dokter,
                'label' => (string) $doctor->nm_dokter,
                'type' => 'Dokter',
            ];
        }

        $pegawai = DB::table('pegawai')
            ->where('nik', $idUser)
            ->first(['nik', 'nama', 'jbtn']);

        if ($pegawai !== null) {
            return [
                'value' => (string) $pegawai->nik,
                'label' => (string) $pegawai->nama,
                'type' => (string) $pegawai->jbtn,
            ];
        }

        return [
            'value' => $idUser,
            'label' => (string) $user->name,
            'type' => (string) $user->type_user,
        ];
    }

    public function dapatMemilihPengisi(User $user): bool
    {
        if (in_array((string) $user->type_user, ['admin', 'superadmin'], true)) {
            return true;
        }

        return DB::table('uxui_auth_users')
            ->where('id', $user->getAuthIdentifier())
            ->whereIn('alias_group', ['superadmin', 'admin'])
            ->exists();
    }

    /**
     * @return list<array{value:string,label:string,type:string}>
     */
    public function opsiPengisi(): array
    {
        $dokter = DB::table('dokter')
            ->where('status', '1')
            ->orderBy('nm_dokter')
            ->get(['kd_dokter', 'nm_dokter'])
            ->map(fn ($item): array => [
                'value' => (string) $item->kd_dokter,
                'label' => (string) $item->nm_dokter.' - Dokter',
                'type' => 'dokter',
            ]);

        $petugas = DB::table('petugas')
            ->where('status', '1')
            ->orderBy('nama')
            ->get(['nip', 'nama'])
            ->map(fn ($item): array => [
                'value' => (string) $item->nip,
                'label' => (string) $item->nama.' - Petugas',
                'type' => 'petugas',
            ]);

        return $dokter->concat($petugas)
            ->unique('value')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function simpanCppt(User $user, array $data, string $asal): void
    {
        $context = $this->konteksPasien((string) $data['no_rawat'], $asal);

        if ($context === null) {
            throw new \InvalidArgumentException('Data kunjungan pasien tidak ditemukan.');
        }

        $table = $context['status_lanjut'] === 'Ranap' ? 'pemeriksaan_ranap' : 'pemeriksaan_ralan';
        $nip = $this->dapatMemilihPengisi($user)
            ? (string) $data['nip']
            : $this->pengisiSaatIni($user)['value'];

        DB::transaction(function () use ($table, $data, $context, $nip, $user, $asal): void {
            $payload = [
                'no_rawat' => (string) $data['no_rawat'],
                'tgl_perawatan' => (string) $data['tgl_perawatan'],
                'jam_rawat' => strlen((string) $data['jam_rawat']) === 5 ? $data['jam_rawat'].':00' : (string) $data['jam_rawat'],
                'suhu_tubuh' => $this->nullableString($data['suhu_tubuh'] ?? null),
                'tensi' => $this->stringAtauStrip($data['tensi'] ?? null),
                'nadi' => $this->nullableString($data['nadi'] ?? null),
                'respirasi' => $this->nullableString($data['respirasi'] ?? null),
                'tinggi' => $this->nullableString($data['tinggi'] ?? null),
                'berat' => $this->nullableString($data['berat'] ?? null),
                'spo2' => $this->stringAtauStrip($data['spo2'] ?? null),
                'gcs' => $this->nullableString($data['gcs'] ?? null),
                'kesadaran' => (string) $data['kesadaran'],
                'keluhan' => $this->nullableString($data['keluhan'] ?? null),
                'pemeriksaan' => $this->nullableString($data['pemeriksaan'] ?? null),
                'alergi' => $this->nullableString($data['alergi'] ?? null),
                'rtl' => $this->stringAtauStrip($data['rtl'] ?? null),
                'penilaian' => $this->stringAtauStrip($data['penilaian'] ?? null),
                'instruksi' => $this->stringAtauStrip($data['instruksi'] ?? null),
                'evaluasi' => $this->stringAtauStrip($data['evaluasi'] ?? null),
                'nip' => $nip,
            ];

            if ($table === 'pemeriksaan_ralan') {
                $payload['imun_ke'] = $this->nullableString($data['imun_ke'] ?? null);
                $payload['lingkar_perut'] = $this->nullableString($data['lingkar_perut'] ?? null);
            }

            DB::table($table)->insert($payload);
            $this->simpanTtvTerbaru((string) $data['no_rawat'], $data, (string) $user->getAuthIdentifier());
            $this->tandaiPemeriksaan((string) $context['no_rawat'], (string) $context['no_rkm_medis'], $user, $asal);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ubahCppt(User $user, array $data): void
    {
        $key = $this->normalisasiKunciCppt($data);
        $row = $this->ambilCppt($key);

        if ($row === null) {
            throw ValidationException::withMessages([
                'cppt' => 'Catatan CPPT yang akan diubah tidak ditemukan. Muat ulang halaman lalu coba kembali.',
            ]);
        }

        if (! $this->dapatMengubahCatatanPemeriksaan($user, (string) $row->nip)) {
            throw ValidationException::withMessages([
                'cppt' => 'Anda tidak memiliki izin mengubah CPPT ini. Hanya superadmin atau pembuat catatan yang dapat mengubahnya.',
            ]);
        }

        $payload = $this->payloadCppt($data, (string) $key['sumber']);

        DB::transaction(function () use ($key, $payload, $data, $user): void {
            DB::table((string) $key['sumber'])
                ->where('no_rawat', (string) $key['no_rawat'])
                ->whereDate('tgl_perawatan', (string) $key['tgl_perawatan'])
                ->where('jam_rawat', (string) $key['jam_rawat'])
                ->update($payload);

            $this->simpanTtvTerbaru((string) $key['no_rawat'], $data, (string) $user->getAuthIdentifier());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function hapusCppt(User $user, array $data): void
    {
        $key = $this->normalisasiKunciCppt($data);
        $row = $this->ambilCppt($key);

        if ($row === null) {
            throw ValidationException::withMessages([
                'cppt' => 'Catatan CPPT yang akan dihapus tidak ditemukan. Muat ulang halaman lalu coba kembali.',
            ]);
        }

        if (! $this->dapatMengubahCatatanPemeriksaan($user, (string) $row->nip)) {
            throw ValidationException::withMessages([
                'cppt' => 'Anda tidak memiliki izin menghapus CPPT ini. Hanya superadmin atau pembuat catatan yang dapat menghapusnya.',
            ]);
        }

        DB::table((string) $key['sumber'])
            ->where('no_rawat', (string) $key['no_rawat'])
            ->whereDate('tgl_perawatan', (string) $key['tgl_perawatan'])
            ->where('jam_rawat', (string) $key['jam_rawat'])
            ->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    /**
     * @param  array{scope?:string,no_rawat?:string,tgl_awal?:string|null,tgl_akhir?:string|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function riwayatCppt(string $noRkmMedis, ?int $limit = null, array $filters = [], ?User $user = null): array
    {
        $ralan = DB::table('pemeriksaan_ralan')
            ->join('reg_periksa', 'pemeriksaan_ralan.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pegawai', 'pemeriksaan_ralan.nip', '=', 'pegawai.nik')
            ->where('reg_periksa.no_rkm_medis', $noRkmMedis)
            ->select($this->selectRiwayatCppt('pemeriksaan_ralan', 'Rawat Jalan'));

        $ranap = DB::table('pemeriksaan_ranap')
            ->join('reg_periksa', 'pemeriksaan_ranap.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pegawai', 'pemeriksaan_ranap.nip', '=', 'pegawai.nik')
            ->where('reg_periksa.no_rkm_medis', $noRkmMedis)
            ->select($this->selectRiwayatCppt('pemeriksaan_ranap', 'Rawat Inap'));

        $this->filterRiwayatCppt($ralan, 'pemeriksaan_ralan', $filters);
        $this->filterRiwayatCppt($ranap, 'pemeriksaan_ranap', $filters);

        /** @var Collection<int, object> $rows */
        $rows = $ralan->unionAll($ranap)
            ->orderByDesc('tgl_perawatan')
            ->orderByDesc('jam_rawat')
            ->when($limit !== null, fn (Builder $query): Builder => $query->limit($limit))
            ->get();

        return $rows
            ->map(function ($row) use ($user): array {
                $history = (array) $row;
                $history['can_view'] = $user !== null;
                $history['can_edit'] = $user !== null && $this->dapatMengubahCatatanPemeriksaan($user, (string) $row->nip);
                $history['can_delete'] = $history['can_edit'];

                return $history;
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function riwayatPasien(string $noRkmMedis, ?int $limit = null): array
    {
        $query = DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('dokter', 'reg_periksa.kd_dokter', '=', 'dokter.kd_dokter')
            ->join('poliklinik', 'reg_periksa.kd_poli', '=', 'poliklinik.kd_poli')
            ->join('penjab', 'reg_periksa.kd_pj', '=', 'penjab.kd_pj')
            ->where('reg_periksa.no_rkm_medis', $noRkmMedis)
            ->where('reg_periksa.stts', '<>', 'Batal')
            ->select([
                'reg_periksa.no_rawat',
                'reg_periksa.no_reg',
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.kd_poli',
                'reg_periksa.kd_pj',
                'reg_periksa.stts',
                'reg_periksa.stts_daftar',
                'reg_periksa.status_lanjut',
                'pasien.no_rkm_medis',
                'pasien.nm_pasien',
                'dokter.nm_dokter',
                'poliklinik.nm_poli',
                'penjab.png_jawab',
                DB::raw('(select no_sep from bridging_sep where bridging_sep.no_rawat = reg_periksa.no_rawat order by bridging_sep.tglsep desc, bridging_sep.jam_sep desc limit 1) as no_sep'),
                DB::raw('(select nmdiagnosaawal from bridging_sep where bridging_sep.no_rawat = reg_periksa.no_rawat order by bridging_sep.tglsep desc, bridging_sep.jam_sep desc limit 1) as diagnosa_sep'),
                DB::raw("(select concat(diagnosa_pasien.kd_penyakit, ' - ', penyakit.nm_penyakit) from diagnosa_pasien left join penyakit on penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit where diagnosa_pasien.no_rawat = reg_periksa.no_rawat order by diagnosa_pasien.prioritas asc limit 1) as diagnosa_pasien"),
                DB::raw('(select count(*) from pemeriksaan_ralan where pemeriksaan_ralan.no_rawat = reg_periksa.no_rawat) as jumlah_cppt_ralan'),
                DB::raw('(select count(*) from pemeriksaan_ranap where pemeriksaan_ranap.no_rawat = reg_periksa.no_rawat) as jumlah_cppt_ranap'),
                DB::raw('(select count(*) from periksa_lab where periksa_lab.no_rawat = reg_periksa.no_rawat) as jumlah_lab'),
                DB::raw('(select count(*) from periksa_radiologi where periksa_radiologi.no_rawat = reg_periksa.no_rawat) as jumlah_radiologi'),
                DB::raw('(select count(*) from resep_obat where resep_obat.no_rawat = reg_periksa.no_rawat) as jumlah_resep'),
            ])
            ->orderByDesc('reg_periksa.tgl_registrasi')
            ->orderByDesc('reg_periksa.jam_reg');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get()
            ->map(function ($row): array {
                $jumlahCpptRalan = (int) $row->jumlah_cppt_ralan;
                $jumlahCpptRanap = (int) $row->jumlah_cppt_ranap;

                return [
                    'no_rawat' => (string) $row->no_rawat,
                    'no_reg' => (string) $row->no_reg,
                    'tgl_registrasi' => (string) $row->tgl_registrasi,
                    'jam_reg' => (string) $row->jam_reg,
                    'no_rkm_medis' => (string) $row->no_rkm_medis,
                    'nm_pasien' => (string) $row->nm_pasien,
                    'nm_dokter' => (string) $row->nm_dokter,
                    'kd_poli' => (string) $row->kd_poli,
                    'nm_poli' => (string) $row->nm_poli,
                    'kd_pj' => (string) $row->kd_pj,
                    'png_jawab' => (string) $row->png_jawab,
                    'stts' => (string) $row->stts,
                    'stts_daftar' => (string) $row->stts_daftar,
                    'status_lanjut' => (string) $row->status_lanjut,
                    'no_sep' => $row->no_sep ? (string) $row->no_sep : null,
                    'diagnosa_sep' => $row->diagnosa_sep ? (string) $row->diagnosa_sep : null,
                    'diagnosa_pasien' => $row->diagnosa_pasien ? (string) $row->diagnosa_pasien : null,
                    'jumlah_cppt' => $jumlahCpptRalan + $jumlahCpptRanap,
                    'jumlah_lab' => (int) $row->jumlah_lab,
                    'jumlah_radiologi' => (int) $row->jumlah_radiologi,
                    'jumlah_resep' => (int) $row->jumlah_resep,
                ];
            })
            ->all();
    }

    /**
     * @return list<Expression|string>
     */
    private function selectRiwayatCppt(string $table, string $asal): array
    {
        return [
            "{$table}.no_rawat",
            "{$table}.tgl_perawatan",
            "{$table}.jam_rawat",
            "{$table}.suhu_tubuh",
            "{$table}.tensi",
            "{$table}.nadi",
            "{$table}.respirasi",
            "{$table}.tinggi",
            "{$table}.berat",
            "{$table}.spo2",
            "{$table}.gcs",
            "{$table}.kesadaran",
            "{$table}.keluhan",
            "{$table}.pemeriksaan",
            "{$table}.alergi",
            "{$table}.rtl",
            "{$table}.penilaian",
            "{$table}.instruksi",
            "{$table}.evaluasi",
            "{$table}.nip",
            'pegawai.nama as nama_pengisi',
            DB::raw("'{$asal}' as asal_layanan"),
            DB::raw("'{$table}' as sumber"),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payloadCppt(array $data, string $table): array
    {
        $payload = [
            'suhu_tubuh' => $this->nullableString($data['suhu_tubuh'] ?? null),
            'tensi' => $this->stringAtauStrip($data['tensi'] ?? null),
            'nadi' => $this->nullableString($data['nadi'] ?? null),
            'respirasi' => $this->nullableString($data['respirasi'] ?? null),
            'tinggi' => $this->nullableString($data['tinggi'] ?? null),
            'berat' => $this->nullableString($data['berat'] ?? null),
            'spo2' => $this->stringAtauStrip($data['spo2'] ?? null),
            'gcs' => $this->nullableString($data['gcs'] ?? null),
            'kesadaran' => (string) $data['kesadaran'],
            'keluhan' => $this->nullableString($data['keluhan'] ?? null),
            'pemeriksaan' => $this->nullableString($data['pemeriksaan'] ?? null),
            'alergi' => $this->nullableString($data['alergi'] ?? null),
            'rtl' => $this->stringAtauStrip($data['rtl'] ?? null),
            'penilaian' => $this->stringAtauStrip($data['penilaian'] ?? null),
            'instruksi' => $this->stringAtauStrip($data['instruksi'] ?? null),
            'evaluasi' => $this->stringAtauStrip($data['evaluasi'] ?? null),
            'nip' => (string) $data['nip'],
        ];

        if ($table === 'pemeriksaan_ralan') {
            $payload['imun_ke'] = $this->nullableString($data['imun_ke'] ?? null);
            $payload['lingkar_perut'] = $this->nullableString($data['lingkar_perut'] ?? null);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function simpanTtvTerbaru(string $noRawat, array $data, string $idUser): void
    {
        $now = now();
        $payload = [
            'suhu_tubuh' => $this->nullableString($data['suhu_tubuh'] ?? null),
            'tensi' => $this->nullableString($data['tensi'] ?? null),
            'nadi' => $this->nullableInteger($data['nadi'] ?? null),
            'respirasi' => $this->nullableInteger($data['respirasi'] ?? null),
            'spo2' => $this->nullableInteger($data['spo2'] ?? null),
            'tinggi' => $this->nullableString($data['tinggi'] ?? null),
            'berat' => $this->nullableString($data['berat'] ?? null),
            'gcs' => $this->nullableString($data['gcs'] ?? null),
            'kesadaran' => $this->nullableString($data['kesadaran'] ?? null),
            'updated_by' => $idUser,
            'updated_at' => $now,
        ];

        $query = DB::table('ttv_pasien')->where('no_rawat', $noRawat);

        if ($query->exists()) {
            $query->update($payload);
        } else {
            DB::table('ttv_pasien')->insert([
                'no_rawat' => $noRawat,
                ...$payload,
                'created_by' => $idUser,
                'created_at' => $now,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{no_rawat:string,tgl_perawatan:string,jam_rawat:string,sumber:string}
     */
    private function normalisasiKunciCppt(array $data): array
    {
        return [
            'no_rawat' => (string) $data['key_no_rawat'],
            'tgl_perawatan' => (string) $data['key_tgl_perawatan'],
            'jam_rawat' => strlen((string) $data['key_jam_rawat']) === 5 ? $data['key_jam_rawat'].':00' : (string) $data['key_jam_rawat'],
            'sumber' => (string) $data['key_sumber'],
        ];
    }

    /**
     * @param  array{no_rawat:string,tgl_perawatan:string,jam_rawat:string,sumber:string}  $key
     */
    private function ambilCppt(array $key): ?object
    {
        return DB::table($key['sumber'])
            ->where('no_rawat', $key['no_rawat'])
            ->whereDate('tgl_perawatan', $key['tgl_perawatan'])
            ->where('jam_rawat', $key['jam_rawat'])
            ->first();
    }

    /**
     * @param  array{scope?:string,no_rawat?:string,tgl_awal?:string|null,tgl_akhir?:string|null}  $filters
     */
    private function filterRiwayatCppt(Builder $query, string $table, array $filters): void
    {
        if (($filters['scope'] ?? 'kunjungan') === 'kunjungan' && ! empty($filters['no_rawat'])) {
            $query->where("{$table}.no_rawat", (string) $filters['no_rawat']);

            return;
        }

        if (! empty($filters['tgl_awal'])) {
            $query->whereDate("{$table}.tgl_perawatan", '>=', (string) $filters['tgl_awal']);
        }

        if (! empty($filters['tgl_akhir'])) {
            $query->whereDate("{$table}.tgl_perawatan", '<=', (string) $filters['tgl_akhir']);
        }
    }

    private function tandaiPemeriksaan(string $noRawat, string $noRkmMedis, User $user, string $asal): void
    {
        $table = (string) $user->type_user === 'dokter'
            ? 'uxui_tindakan_pasien'
            : 'uxui_tindakan_pasien_perawat';

        DB::table($table)->updateOrInsert(
            [
                'no_rawat' => $noRawat,
                'no_rkm_medis' => $noRkmMedis,
                'type_akses' => $this->typeAkses($asal),
            ],
            ['pemeriksaan' => '1'],
        );
    }

    private function asalDariRegistrasi(string $statusLanjut, string $kdPoli): string
    {
        if ($statusLanjut === 'Ranap') {
            return 'rawat-inap';
        }

        if ($kdPoli === self::KODE_POLI_IGD) {
            return 'igd';
        }

        return 'rawat-jalan';
    }

    private function hrefMenuPemeriksaan(array $menu, string $noRawat, ?string $asal): string
    {
        $parameters = [
            'no_rawat' => $noRawat,
            'fr' => $this->kodeAsal($asal),
        ];

        if (($menu['route'] ?? '') === 'rme.pemeriksaan.placeholder') {
            $parameters['menu'] = $menu['key'];
        }

        return route((string) $menu['route'], $parameters);
    }

    public function normalisasiAsal(?string $asal): ?string
    {
        return match ($asal) {
            'rj' => 'rawat-jalan',
            'ri' => 'rawat-inap',
            'rp' => 'rujukan-internal',
            'igd' => 'igd',
            default => $asal,
        };
    }

    public function kodeAsal(?string $asal): ?string
    {
        return match ($this->normalisasiAsal($asal)) {
            'rawat-inap' => 'ri',
            'igd' => 'igd',
            'rujukan-internal' => 'rp',
            'rawat-jalan' => 'rj',
            default => null,
        };
    }

    public function isSuperadmin(User $user): bool
    {
        if ((string) $user->type_user === 'superadmin') {
            return true;
        }

        return DB::table('uxui_auth_users')
            ->where('id', $user->getAuthIdentifier())
            ->where('alias_group', 'superadmin')
            ->exists();
    }

    private function dapatMengubahCatatanPemeriksaan(User $user, string $nipPembuat): bool
    {
        if ($this->isSuperadmin($user)) {
            return true;
        }

        return $this->pengisiSaatIni($user)['value'] === $nipPembuat;
    }

    private function typeAkses(string $asal): string
    {
        return match ($asal) {
            'rawat-inap' => 'ri',
            'igd' => 'igd',
            'rujukan-internal' => 'rp',
            default => 'rj',
        };
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : (int) $value;
    }

    private function stringAtauStrip(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? '-' : $value;
    }
}
