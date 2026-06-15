<?php

namespace App\Services\Rme;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class RmeService
{
    private const KODE_POLI_IGD = 'IGDK';

    /**
     * @return array{suhu_tubuh:string,tensi:string,nadi:string,respirasi:string,spo2:string,tinggi:string,berat:string,gcs:string,kesadaran:string}
     */
    public function ttvPasien(string $noRawat): array
    {
        $ttv = DB::table('ttv_pasien')
            ->where('no_rawat', $noRawat)
            ->first([
                'suhu_tubuh',
                'tensi',
                'nadi',
                'respirasi',
                'spo2',
                'tinggi',
                'berat',
                'gcs',
                'kesadaran',
            ]);

        return [
            'suhu_tubuh' => $ttv?->suhu_tubuh ? (string) $ttv->suhu_tubuh : '',
            'tensi' => $ttv?->tensi ? (string) $ttv->tensi : '',
            'nadi' => $ttv?->nadi !== null ? (string) $ttv->nadi : '',
            'respirasi' => $ttv?->respirasi !== null ? (string) $ttv->respirasi : '',
            'spo2' => $ttv?->spo2 !== null ? (string) $ttv->spo2 : '',
            'tinggi' => $ttv?->tinggi ? (string) $ttv->tinggi : '',
            'berat' => $ttv?->berat ? (string) $ttv->berat : '',
            'gcs' => $ttv?->gcs ? (string) $ttv->gcs : '',
            'kesadaran' => $ttv?->kesadaran ? (string) $ttv->kesadaran : 'Compos Mentis',
        ];
    }

    /**
     * @param  array{no_rawat:string,suhu_tubuh?:string|null,tensi:string,nadi?:int|string|null,respirasi?:int|string|null,spo2?:int|string|null,tinggi?:string|null,berat?:string|null,gcs?:string|null,kesadaran:string}  $data
     * @return array{berhasil:bool,pesan:string}
     */
    public function simpanTtvPasien(array $data, ?string $idUser): array
    {
        if (! DB::table('reg_periksa')->where('no_rawat', $data['no_rawat'])->exists()) {
            return [
                'berhasil' => false,
                'pesan' => 'Kunjungan pasien tidak ditemukan. Muat ulang daftar pasien lalu coba kembali.',
            ];
        }

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

        $query = DB::table('ttv_pasien')->where('no_rawat', $data['no_rawat']);

        if ($query->exists()) {
            $query->update($payload);
        } else {
            DB::table('ttv_pasien')->insert([
                'no_rawat' => $data['no_rawat'],
                ...$payload,
                'created_by' => $idUser,
                'created_at' => $now,
            ]);
        }

        return [
            'berhasil' => true,
            'pesan' => 'Tanda vital pasien berhasil disimpan.',
        ];
    }

    /**
     * @param  array{tgl_awal:string,tgl_akhir:string,kd_poli?:string|null,kd_pj?:string|null,status?:string|null,search?:string|null,order?:string|null}  $filter
     */
    public function pasienRawatJalan(array $filter): LengthAwarePaginator
    {
        $order = ($filter['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $this->queryRegistrasiDasar()
            ->where('reg_periksa.status_lanjut', 'Ralan')
            ->where('reg_periksa.kd_poli', '<>', self::KODE_POLI_IGD)
            ->when(filled($filter['kd_poli'] ?? null), fn (Builder $query): Builder => $query->where('reg_periksa.kd_poli', $filter['kd_poli']))
            ->when(filled($filter['kd_pj'] ?? null), fn (Builder $query): Builder => $query->where('reg_periksa.kd_pj', $filter['kd_pj']))
            ->when(filled($filter['status'] ?? null), fn (Builder $query): Builder => $query->where('reg_periksa.stts', $filter['status']))
            ->when(filled($filter['search'] ?? null), fn (Builder $query): Builder => $this->filterPencarianRegistrasi($query, (string) $filter['search']))
            ->whereBetween('reg_periksa.tgl_registrasi', [$filter['tgl_awal'], $filter['tgl_akhir']])
            ->orderBy('reg_periksa.tgl_registrasi', $order)
            ->orderBy('reg_periksa.jam_reg', $order)
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @param  array{tgl_awal:string,tgl_akhir:string,status?:string|null,search?:string|null}  $filter
     */
    public function pasienIgd(array $filter): LengthAwarePaginator
    {
        return $this->queryRegistrasiDasar()
            ->where('reg_periksa.status_lanjut', 'Ralan')
            ->where('reg_periksa.kd_poli', self::KODE_POLI_IGD)
            ->when(filled($filter['status'] ?? null), fn (Builder $query): Builder => $query->where('reg_periksa.stts', $filter['status']))
            ->when(filled($filter['search'] ?? null), fn (Builder $query): Builder => $this->filterPencarianRegistrasi($query, (string) $filter['search']))
            ->whereBetween('reg_periksa.tgl_registrasi', [$filter['tgl_awal'], $filter['tgl_akhir']])
            ->orderByDesc('reg_periksa.tgl_registrasi')
            ->orderByDesc('reg_periksa.jam_reg')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @return array{berhasil:bool,pesan:string}
     */
    public function ubahDokterIgd(string $noRawat, string $kodeDokter): array
    {
        $registration = DB::table('reg_periksa')
            ->where('no_rawat', $noRawat)
            ->where('kd_poli', self::KODE_POLI_IGD)
            ->where('status_lanjut', 'Ralan')
            ->first(['no_rawat']);

        if ($registration === null) {
            return [
                'berhasil' => false,
                'pesan' => 'Pendaftaran IGD tidak ditemukan atau pasien bukan pasien IGD aktif.',
            ];
        }

        DB::table('reg_periksa')
            ->where('no_rawat', $noRawat)
            ->update(['kd_dokter' => $kodeDokter]);

        return [
            'berhasil' => true,
            'pesan' => 'Dokter penanggung jawab IGD berhasil diperbarui.',
        ];
    }

    /**
     * @return array{berhasil:bool,pesan:string}
     */
    public function simpanDiagnosaIgd(string $noRawat, string $kodePenyakit): array
    {
        $registration = DB::table('reg_periksa')
            ->where('no_rawat', $noRawat)
            ->where('kd_poli', self::KODE_POLI_IGD)
            ->where('status_lanjut', 'Ralan')
            ->first(['no_rawat']);

        if ($registration === null) {
            return [
                'berhasil' => false,
                'pesan' => 'Pendaftaran IGD tidak ditemukan atau pasien bukan pasien IGD aktif.',
            ];
        }

        DB::transaction(function () use ($noRawat, $kodePenyakit): void {
            DB::table('diagnosa_pasien')
                ->where('no_rawat', $noRawat)
                ->where('status', 'Ralan')
                ->where('prioritas', 1)
                ->delete();

            DB::table('diagnosa_pasien')->insert([
                'no_rawat' => $noRawat,
                'kd_penyakit' => $kodePenyakit,
                'status' => 'Ralan',
                'prioritas' => 1,
                'status_penyakit' => 'Baru',
            ]);
        });

        return [
            'berhasil' => true,
            'pesan' => 'Diagnosa IGD berhasil disimpan.',
        ];
    }

    /**
     * @param  array{tgl_awal:string,tgl_akhir:string,kd_bangsal?:string|null,tipe_filter_ranap?:string|null,search?:string|null}  $filter
     */
    public function pasienRawatInap(array $filter): LengthAwarePaginator
    {
        $kamarTerakhir = DB::table('kamar_inap as kamar_urut')
            ->select([
                'kamar_urut.no_rawat',
                DB::raw("MAX(CONCAT(kamar_urut.tgl_masuk, ' ', kamar_urut.jam_masuk)) as waktu_masuk_terakhir"),
            ])
            ->groupBy('kamar_urut.no_rawat');

        return DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('dokter', 'reg_periksa.kd_dokter', '=', 'dokter.kd_dokter')
            ->join('penjab', 'reg_periksa.kd_pj', '=', 'penjab.kd_pj')
            ->joinSub($kamarTerakhir, 'kamar_terakhir', function ($join): void {
                $join->on('kamar_terakhir.no_rawat', '=', 'reg_periksa.no_rawat');
            })
            ->join('kamar_inap', function ($join): void {
                $join->on('kamar_inap.no_rawat', '=', 'kamar_terakhir.no_rawat')
                    ->whereRaw("CONCAT(kamar_inap.tgl_masuk, ' ', kamar_inap.jam_masuk) = kamar_terakhir.waktu_masuk_terakhir");
            })
            ->join('kamar', 'kamar_inap.kd_kamar', '=', 'kamar.kd_kamar')
            ->join('bangsal', 'kamar.kd_bangsal', '=', 'bangsal.kd_bangsal')
            ->leftJoin('bridging_sep', function ($join): void {
                $join->on('bridging_sep.no_rawat', '=', 'reg_periksa.no_rawat')
                    ->where('bridging_sep.jnspelayanan', '1');
            })
            ->select([
                'reg_periksa.no_rawat',
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.kd_dokter',
                'reg_periksa.no_rkm_medis',
                'reg_periksa.kd_pj',
                'reg_periksa.stts',
                'reg_periksa.stts_daftar',
                'reg_periksa.status_lanjut',
                'reg_periksa.status_bayar',
                'pasien.nm_pasien',
                'pasien.jk',
                'pasien.tgl_lahir',
                'pasien.no_peserta',
                'pasien.no_ktp',
                'dokter.nm_dokter',
                'penjab.png_jawab',
                'kamar_inap.kd_kamar',
                'kamar_inap.trf_kamar',
                'kamar_inap.diagnosa_awal',
                'kamar_inap.diagnosa_akhir',
                'kamar_inap.tgl_masuk',
                'kamar_inap.jam_masuk',
                'kamar_inap.tgl_keluar',
                'kamar_inap.jam_keluar',
                'kamar_inap.lama',
                'kamar_inap.ttl_biaya',
                'kamar_inap.stts_pulang',
                'kamar.kelas',
                'bangsal.kd_bangsal',
                'bangsal.nm_bangsal',
                'bridging_sep.no_sep',
                'bridging_sep.tglsep',
                'bridging_sep.jam_sep',
                'bridging_sep.diagawal',
                'bridging_sep.nmdiagnosaawal',
                DB::raw("CONCAT(reg_periksa.umurdaftar, ' ', reg_periksa.sttsumur) as umur_registrasi"),
                DB::raw('EXISTS(SELECT 1 FROM bridging_sep as sep WHERE sep.no_rawat = reg_periksa.no_rawat AND sep.jnspelayanan = "1") as ada_sep'),
                DB::raw('EXISTS(SELECT 1 FROM uxui_tindakan_pasien WHERE uxui_tindakan_pasien.no_rawat = reg_periksa.no_rawat AND uxui_tindakan_pasien.no_rkm_medis = reg_periksa.no_rkm_medis AND uxui_tindakan_pasien.type_akses = "ri" AND uxui_tindakan_pasien.pemeriksaan = "1") as sudah_diperiksa_dokter'),
                DB::raw('EXISTS(SELECT 1 FROM uxui_tindakan_pasien_perawat WHERE uxui_tindakan_pasien_perawat.no_rawat = reg_periksa.no_rawat AND uxui_tindakan_pasien_perawat.no_rkm_medis = reg_periksa.no_rkm_medis AND uxui_tindakan_pasien_perawat.type_akses = "ri" AND uxui_tindakan_pasien_perawat.pemeriksaan = "1") as sudah_diperiksa_perawat'),
                DB::raw('(SELECT GROUP_CONCAT(CONCAT(dpjp_ranap.kd_dokter, "::", dokter_dpjp.nm_dokter) ORDER BY dokter_dpjp.nm_dokter SEPARATOR "||") FROM dpjp_ranap INNER JOIN dokter as dokter_dpjp ON dokter_dpjp.kd_dokter = dpjp_ranap.kd_dokter WHERE dpjp_ranap.no_rawat = reg_periksa.no_rawat) as dokter_pj_ranap'),
            ])
            ->where('reg_periksa.status_lanjut', 'Ranap')
            ->where('reg_periksa.stts', '<>', 'Batal')
            ->when(true, fn (Builder $query): Builder => $this->filterWaktuRawatInap($query, $filter))
            ->when(filled($filter['kd_bangsal'] ?? null), fn (Builder $query): Builder => $query->where('bangsal.kd_bangsal', $filter['kd_bangsal']))
            ->when(filled($filter['search'] ?? null), fn (Builder $query): Builder => $this->filterPencarianRawatInap($query, (string) $filter['search']))
            ->orderByDesc('kamar_inap.tgl_masuk')
            ->orderByDesc('kamar_inap.jam_masuk')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @param  array{tgl_awal:string,tgl_akhir:string,kd_poli_asal?:string|null,kd_poli?:string|null,search?:string|null,order?:string|null}  $filter
     */
    public function pasienRujukanInternal(array $filter): LengthAwarePaginator
    {
        $order = ($filter['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return DB::table('rujukan_internal_poli')
            ->join('reg_periksa', 'rujukan_internal_poli.no_rawat', '=', 'reg_periksa.no_rawat')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('dokter as dokter_awal', 'reg_periksa.kd_dokter', '=', 'dokter_awal.kd_dokter')
            ->join('poliklinik as poli_awal', 'reg_periksa.kd_poli', '=', 'poli_awal.kd_poli')
            ->join('dokter as dokter_tujuan', 'rujukan_internal_poli.kd_dokter', '=', 'dokter_tujuan.kd_dokter')
            ->join('poliklinik as poli_tujuan', 'rujukan_internal_poli.kd_poli', '=', 'poli_tujuan.kd_poli')
            ->join('penjab', 'reg_periksa.kd_pj', '=', 'penjab.kd_pj')
            ->select([
                'reg_periksa.no_rawat',
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.no_rkm_medis',
                'reg_periksa.stts',
                'reg_periksa.stts_daftar',
                'reg_periksa.status_lanjut',
                'reg_periksa.status_bayar',
                'pasien.nm_pasien',
                'pasien.jk',
                'pasien.tgl_lahir',
                'pasien.no_peserta',
                'pasien.no_ktp',
                'penjab.png_jawab',
                'dokter_awal.nm_dokter as nm_dokter_awal',
                'poli_awal.kd_poli as kd_poli_awal',
                'poli_awal.nm_poli as nm_poli_awal',
                'rujukan_internal_poli.kd_dokter as kd_dokter_tujuan',
                'dokter_tujuan.nm_dokter as nm_dokter_tujuan',
                'rujukan_internal_poli.kd_poli as kd_poli_tujuan',
                'poli_tujuan.nm_poli as nm_poli_tujuan',
                DB::raw("CONCAT(reg_periksa.umurdaftar, ' ', reg_periksa.sttsumur) as umur_registrasi"),
                DB::raw('EXISTS(SELECT 1 FROM uxui_tindakan_pasien WHERE uxui_tindakan_pasien.no_rawat = reg_periksa.no_rawat AND uxui_tindakan_pasien.no_rkm_medis = reg_periksa.no_rkm_medis AND uxui_tindakan_pasien.type_akses = "rp" AND uxui_tindakan_pasien.pemeriksaan = "1") as sudah_diperiksa_dokter'),
                DB::raw('EXISTS(SELECT 1 FROM uxui_tindakan_pasien_perawat WHERE uxui_tindakan_pasien_perawat.no_rawat = reg_periksa.no_rawat AND uxui_tindakan_pasien_perawat.no_rkm_medis = reg_periksa.no_rkm_medis AND uxui_tindakan_pasien_perawat.type_akses = "rp" AND uxui_tindakan_pasien_perawat.pemeriksaan = "1") as sudah_diperiksa_perawat'),
                DB::raw('EXISTS(SELECT 1 FROM kamar_inap WHERE kamar_inap.no_rawat = reg_periksa.no_rawat) as is_ranap'),
            ])
            ->whereBetween('reg_periksa.tgl_registrasi', [$filter['tgl_awal'], $filter['tgl_akhir']])
            ->when(filled($filter['kd_poli_asal'] ?? null), fn (Builder $query): Builder => $query->where('reg_periksa.kd_poli', $filter['kd_poli_asal']))
            ->when(filled($filter['kd_poli'] ?? null), fn (Builder $query): Builder => $query->where('rujukan_internal_poli.kd_poli', $filter['kd_poli']))
            ->when(filled($filter['search'] ?? null), fn (Builder $query): Builder => $this->filterPencarianRujukanInternal($query, (string) $filter['search']))
            ->orderBy('reg_periksa.tgl_registrasi', $order)
            ->orderBy('reg_periksa.jam_reg', $order)
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    public function opsiPoliklinik(bool $sertakanIgd = false): array
    {
        return DB::table('poliklinik')
            ->select('kd_poli', 'nm_poli')
            ->where('status', '1')
            ->when(! $sertakanIgd, fn (Builder $query): Builder => $query->where('kd_poli', '<>', self::KODE_POLI_IGD))
            ->orderBy('nm_poli')
            ->get()
            ->map(fn ($poli): array => [
                'value' => $poli->kd_poli,
                'label' => $poli->nm_poli,
            ])
            ->all();
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    public function opsiBangsal(): array
    {
        return DB::table('bangsal')
            ->select('kd_bangsal', 'nm_bangsal')
            ->where('status', '1')
            ->orderBy('nm_bangsal')
            ->get()
            ->map(fn ($bangsal): array => [
                'value' => $bangsal->kd_bangsal,
                'label' => $bangsal->nm_bangsal,
            ])
            ->all();
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    public function opsiPenjamin(): array
    {
        return DB::table('penjab')
            ->select('kd_pj', 'png_jawab')
            ->where('status', '1')
            ->orderBy('png_jawab')
            ->get()
            ->map(fn ($penjamin): array => [
                'value' => $penjamin->kd_pj,
                'label' => $penjamin->png_jawab,
            ])
            ->all();
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    public function opsiDokter(): array
    {
        return DB::table('dokter')
            ->select('kd_dokter', 'nm_dokter')
            ->where('status', '1')
            ->orderBy('nm_dokter')
            ->get()
            ->map(fn ($dokter): array => [
                'value' => $dokter->kd_dokter,
                'label' => $dokter->nm_dokter,
            ])
            ->all();
    }

    /**
     * @return list<array{value:string,label:string,description:string,kelas:string|null,tarif:float,status:string|null}>
     */
    public function opsiKamarKosong(): array
    {
        return DB::table('kamar')
            ->join('bangsal', 'bangsal.kd_bangsal', '=', 'kamar.kd_bangsal')
            ->select('kamar.kd_kamar', 'kamar.trf_kamar', 'kamar.status', 'kamar.kelas', 'bangsal.nm_bangsal')
            ->where('kamar.statusdata', '1')
            ->where('kamar.status', 'KOSONG')
            ->orderBy('bangsal.nm_bangsal')
            ->orderBy('kamar.kd_kamar')
            ->get()
            ->map(fn ($kamar): array => [
                'value' => $kamar->kd_kamar,
                'label' => $kamar->kd_kamar,
                'description' => trim(($kamar->nm_bangsal ?? 'Bangsal tidak tercatat').' | '.($kamar->kelas ?? '-').' | Rp '.number_format((float) $kamar->trf_kamar, 0, ',', '.')),
                'kelas' => $kamar->kelas,
                'tarif' => (float) $kamar->trf_kamar,
                'status' => $kamar->status,
            ])
            ->all();
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    public function opsiStatusPulangRanap(): array
    {
        return collect([
            '-',
            'Sehat',
            'Rujuk',
            'APS',
            '+',
            'Meninggal',
            'Sembuh',
            'Membaik',
            'Pulang Paksa',
            'Pindah Kamar',
            'Status Belum Lengkap',
            'Atas Persetujuan Dokter',
            'Atas Permintaan Sendiri',
            'Isoman',
            'Lain-lain',
        ])->map(fn (string $status): array => [
            'value' => $status,
            'label' => $status === '-' ? 'Masih dirawat' : $status,
        ])->all();
    }

    /**
     * @param  list<string>  $kodeDokter
     * @return array{berhasil:bool,pesan:string}
     */
    public function simpanDokterPenanggungJawabRanap(string $noRawat, array $kodeDokter): array
    {
        if (! $this->pasienRanapAda($noRawat)) {
            return [
                'berhasil' => false,
                'pesan' => 'Pasien rawat inap tidak ditemukan.',
            ];
        }

        $kodeDokter = collect($kodeDokter)
            ->filter(fn (?string $kode): bool => filled($kode))
            ->unique()
            ->values()
            ->all();

        if ($kodeDokter === []) {
            return [
                'berhasil' => false,
                'pesan' => 'Pilih minimal satu dokter penanggung jawab rawat inap.',
            ];
        }

        $jumlahDokterValid = DB::table('dokter')
            ->whereIn('kd_dokter', $kodeDokter)
            ->where('status', '1')
            ->count();

        if ($jumlahDokterValid !== count($kodeDokter)) {
            return [
                'berhasil' => false,
                'pesan' => 'Sebagian dokter yang dipilih tidak valid atau sudah tidak aktif.',
            ];
        }

        DB::transaction(function () use ($noRawat, $kodeDokter): void {
            DB::table('dpjp_ranap')->where('no_rawat', $noRawat)->delete();

            DB::table('dpjp_ranap')->insert(
                collect($kodeDokter)
                    ->map(fn (string $kode): array => [
                        'no_rawat' => $noRawat,
                        'kd_dokter' => $kode,
                    ])
                    ->all()
            );
        });

        return [
            'berhasil' => true,
            'pesan' => 'Dokter penanggung jawab rawat inap berhasil diperbarui.',
        ];
    }

    /**
     * @param  array{kd_kamar:string,tgl_masuk:string,jam_masuk:string,diagnosa_awal?:string|null,opsi_pindah_kamar:string}  $data
     * @return array{berhasil:bool,pesan:string}
     */
    public function pindahKamarRanap(string $noRawat, array $data): array
    {
        $kamarAktif = $this->kamarTerakhirRanap($noRawat);

        if ($kamarAktif === null) {
            return [
                'berhasil' => false,
                'pesan' => 'Data kamar rawat inap aktif tidak ditemukan.',
            ];
        }

        if ($kamarAktif->stts_pulang !== '-') {
            return [
                'berhasil' => false,
                'pesan' => 'Pasien sudah keluar dari rawat inap sehingga tidak bisa dipindah kamar.',
            ];
        }

        if ($kamarAktif->kd_kamar === $data['kd_kamar']) {
            return [
                'berhasil' => false,
                'pesan' => 'Kamar tujuan sama dengan kamar pasien saat ini.',
            ];
        }

        $kamarTujuan = $this->kamarTersedia($data['kd_kamar']);

        if ($kamarTujuan === null) {
            return [
                'berhasil' => false,
                'pesan' => 'Kamar tujuan tidak tersedia atau sudah terisi. Muat ulang halaman lalu pilih kamar lain.',
            ];
        }

        DB::transaction(function () use ($noRawat, $data, $kamarAktif, $kamarTujuan): void {
            $opsiPindahKamar = $data['opsi_pindah_kamar'];
            $waktuKeluar = $this->gabungTanggalJam($data['tgl_masuk'], $data['jam_masuk']);
            $waktuMasukLama = $this->gabungTanggalJam($kamarAktif->tgl_masuk, $kamarAktif->jam_masuk);
            $lama = $this->hitungLamaInap($waktuMasukLama, $waktuKeluar);
            $tarifLama = (float) $kamarAktif->trf_kamar;
            $tarifBaru = (float) $kamarTujuan->trf_kamar;
            $diagnosaAwal = filled($data['diagnosa_awal'] ?? null) ? $data['diagnosa_awal'] : $kamarAktif->diagnosa_awal;

            $queryKamarLama = DB::table('kamar_inap')
                ->where('no_rawat', $noRawat)
                ->where('tgl_masuk', $kamarAktif->tgl_masuk)
                ->where('jam_masuk', $kamarAktif->jam_masuk);

            if ($opsiPindahKamar === '1') {
                $queryKamarLama->update([
                    'kd_kamar' => $kamarTujuan->kd_kamar,
                    'trf_kamar' => $tarifBaru,
                    'diagnosa_awal' => $diagnosaAwal,
                    'tgl_masuk' => $data['tgl_masuk'],
                    'jam_masuk' => $this->normalisasiJam($data['jam_masuk']),
                    'tgl_keluar' => '0000-00-00',
                    'jam_keluar' => '00:00:00',
                    'lama' => 0,
                    'ttl_biaya' => 0,
                    'stts_pulang' => '-',
                ]);
            } elseif ($opsiPindahKamar === '2') {
                $queryKamarLama->update([
                    'kd_kamar' => $kamarTujuan->kd_kamar,
                    'trf_kamar' => $tarifBaru,
                    'diagnosa_awal' => $diagnosaAwal,
                    'ttl_biaya' => (float) ($kamarAktif->lama ?? 0) * $tarifBaru,
                ]);
            } else {
                $tarifLamaUntukBilling = $opsiPindahKamar === '4'
                    ? max($tarifLama, $tarifBaru)
                    : $tarifLama;

                $queryKamarLama->update([
                    'tgl_keluar' => $data['tgl_masuk'],
                    'jam_keluar' => $this->normalisasiJam($data['jam_masuk']),
                    'lama' => $lama,
                    'trf_kamar' => $tarifLamaUntukBilling,
                    'ttl_biaya' => $lama * $tarifLamaUntukBilling,
                    'stts_pulang' => 'Pindah Kamar',
                ]);

                DB::table('kamar_inap')->insert([
                    'no_rawat' => $noRawat,
                    'kd_kamar' => $kamarTujuan->kd_kamar,
                    'trf_kamar' => $tarifBaru,
                    'diagnosa_awal' => $diagnosaAwal,
                    'diagnosa_akhir' => '-',
                    'tgl_masuk' => $data['tgl_masuk'],
                    'jam_masuk' => $this->normalisasiJam($data['jam_masuk']),
                    'tgl_keluar' => '0000-00-00',
                    'jam_keluar' => '00:00:00',
                    'lama' => 0,
                    'ttl_biaya' => 0,
                    'stts_pulang' => '-',
                ]);
            }

            DB::table('kamar')->where('kd_kamar', $kamarAktif->kd_kamar)->update(['status' => 'KOSONG']);
            DB::table('kamar')->where('kd_kamar', $kamarTujuan->kd_kamar)->update(['status' => 'ISI']);

            DB::table('uxui_pasien_pindah_kamar')->insertOrIgnore([
                'no_rkm_medis' => $kamarAktif->no_rkm_medis,
                'no_rawat' => $noRawat,
                'waktu_masuk' => $waktuMasukLama->format('Y-m-d H:i:s'),
                'kd_kamar_awal' => $kamarAktif->kd_kamar,
                'kd_bangsal_awal' => $kamarAktif->kd_bangsal,
                'nm_bangsal_awal' => $kamarAktif->nm_bangsal,
                'trf_kamar_awal' => $tarifLama,
                'waktu_keluar' => $waktuKeluar->format('Y-m-d H:i:s'),
                'kd_kamar_pindah' => $kamarTujuan->kd_kamar,
                'kd_bangsal_pindah' => $kamarTujuan->kd_bangsal,
                'nm_bangsal_pindah' => $kamarTujuan->nm_bangsal,
                'trf_kamar_pindah' => $tarifBaru,
                'lama_inap' => $lama,
                'total' => $lama * $tarifLama,
            ]);
        });

        return [
            'berhasil' => true,
            'pesan' => 'Pasien berhasil dipindahkan ke kamar rawat inap baru.',
        ];
    }

    /**
     * @return array{berhasil:bool,pesan:string}
     */
    public function batalRanap(string $noRawat): array
    {
        if (! $this->pasienRanapAda($noRawat)) {
            return [
                'berhasil' => false,
                'pesan' => 'Pasien rawat inap tidak ditemukan.',
            ];
        }

        $kamarPasien = DB::table('kamar_inap')
            ->where('no_rawat', $noRawat)
            ->pluck('kd_kamar')
            ->all();

        DB::transaction(function () use ($noRawat, $kamarPasien): void {
            DB::table('kamar_inap')->where('no_rawat', $noRawat)->delete();
            DB::table('dpjp_ranap')->where('no_rawat', $noRawat)->delete();

            if ($kamarPasien !== []) {
                DB::table('kamar')->whereIn('kd_kamar', $kamarPasien)->update(['status' => 'KOSONG']);
            }

            $statusPeriksa = DB::table('pemeriksaan_ralan')->where('no_rawat', $noRawat)->exists()
                ? 'Sudah'
                : 'Belum';

            DB::table('reg_periksa')
                ->where('no_rawat', $noRawat)
                ->update([
                    'stts' => $statusPeriksa,
                    'status_lanjut' => 'Ralan',
                ]);
        });

        return [
            'berhasil' => true,
            'pesan' => 'Rawat inap pasien berhasil dibatalkan dan pendaftaran dikembalikan ke rawat jalan.',
        ];
    }

    /**
     * @param  array{tgl_keluar:string,jam_keluar:string,stts_pulang:string,diagnosa_akhir:string}  $data
     * @return array{berhasil:bool,pesan:string}
     */
    public function pulangkanRanap(string $noRawat, array $data): array
    {
        $kamarAktif = $this->kamarTerakhirRanap($noRawat);

        if ($kamarAktif === null) {
            return [
                'berhasil' => false,
                'pesan' => 'Data kamar rawat inap aktif tidak ditemukan.',
            ];
        }

        if ($kamarAktif->stts_pulang !== '-') {
            return [
                'berhasil' => false,
                'pesan' => 'Pasien sudah memiliki status pulang. Gunakan Edit Pulang Pasien untuk mengubahnya.',
            ];
        }

        $this->simpanPulangRanap($noRawat, $kamarAktif, $data);

        return [
            'berhasil' => true,
            'pesan' => 'Pasien rawat inap berhasil dipulangkan.',
        ];
    }

    /**
     * @param  array{tgl_keluar:string,jam_keluar:string,stts_pulang:string,diagnosa_akhir:string}  $data
     * @return array{berhasil:bool,pesan:string}
     */
    public function ubahPulangRanap(string $noRawat, array $data): array
    {
        $kamarTerakhir = $this->kamarTerakhirRanap($noRawat);

        if ($kamarTerakhir === null) {
            return [
                'berhasil' => false,
                'pesan' => 'Data pulang rawat inap tidak ditemukan.',
            ];
        }

        $this->simpanPulangRanap($noRawat, $kamarTerakhir, $data);

        return [
            'berhasil' => true,
            'pesan' => 'Data pulang pasien rawat inap berhasil diperbarui.',
        ];
    }

    /**
     * @return array{berhasil:bool,pesan:string}
     */
    public function ubahStatusPulangRanap(string $noRawat, string $statusPulang): array
    {
        $kamarTerakhir = $this->kamarTerakhirRanap($noRawat);

        if ($kamarTerakhir === null) {
            return [
                'berhasil' => false,
                'pesan' => 'Data kamar rawat inap tidak ditemukan.',
            ];
        }

        DB::transaction(function () use ($noRawat, $statusPulang, $kamarTerakhir): void {
            $data = ['stts_pulang' => $statusPulang];

            if ($statusPulang === '-') {
                $data = [
                    ...$data,
                    'tgl_keluar' => '0000-00-00',
                    'jam_keluar' => '00:00:00',
                    'diagnosa_akhir' => '-',
                    'lama' => 0,
                    'ttl_biaya' => 0,
                ];
            }

            DB::table('kamar_inap')
                ->where('no_rawat', $noRawat)
                ->where('tgl_masuk', $kamarTerakhir->tgl_masuk)
                ->where('jam_masuk', $kamarTerakhir->jam_masuk)
                ->update($data);

            DB::table('kamar')
                ->where('kd_kamar', $kamarTerakhir->kd_kamar)
                ->update(['status' => $statusPulang === '-' ? 'ISI' : 'KOSONG']);
        });

        return [
            'berhasil' => true,
            'pesan' => $statusPulang === '-'
                ? 'Status pasien dikembalikan menjadi masih dirawat.'
                : 'Status pulang pasien berhasil diperbarui.',
        ];
    }

    private function pasienRanapAda(string $noRawat): bool
    {
        return DB::table('reg_periksa')
            ->where('no_rawat', $noRawat)
            ->where('status_lanjut', 'Ranap')
            ->exists();
    }

    private function kamarTerakhirRanap(string $noRawat): ?object
    {
        return DB::table('kamar_inap')
            ->join('kamar', 'kamar.kd_kamar', '=', 'kamar_inap.kd_kamar')
            ->join('bangsal', 'bangsal.kd_bangsal', '=', 'kamar.kd_bangsal')
            ->join('reg_periksa', 'reg_periksa.no_rawat', '=', 'kamar_inap.no_rawat')
            ->select([
                'kamar_inap.no_rawat',
                'reg_periksa.no_rkm_medis',
                'kamar_inap.kd_kamar',
                'kamar_inap.trf_kamar',
                'kamar_inap.diagnosa_awal',
                'kamar_inap.diagnosa_akhir',
                'kamar_inap.tgl_masuk',
                'kamar_inap.jam_masuk',
                'kamar_inap.tgl_keluar',
                'kamar_inap.jam_keluar',
                'kamar_inap.stts_pulang',
                'kamar.kd_bangsal',
                'bangsal.nm_bangsal',
            ])
            ->where('kamar_inap.no_rawat', $noRawat)
            ->orderByDesc('kamar_inap.tgl_masuk')
            ->orderByDesc('kamar_inap.jam_masuk')
            ->first();
    }

    private function kamarTersedia(string $kodeKamar): ?object
    {
        return DB::table('kamar')
            ->join('bangsal', 'bangsal.kd_bangsal', '=', 'kamar.kd_bangsal')
            ->select([
                'kamar.kd_kamar',
                'kamar.kd_bangsal',
                'kamar.trf_kamar',
                'kamar.status',
                'kamar.kelas',
                'bangsal.nm_bangsal',
            ])
            ->where('kamar.kd_kamar', $kodeKamar)
            ->where('kamar.statusdata', '1')
            ->where('kamar.status', 'KOSONG')
            ->first();
    }

    /**
     * @param  array{tgl_keluar:string,jam_keluar:string,stts_pulang:string,diagnosa_akhir:string}  $data
     */
    private function simpanPulangRanap(string $noRawat, object $kamar, array $data): void
    {
        DB::transaction(function () use ($noRawat, $kamar, $data): void {
            $waktuMasuk = $this->gabungTanggalJam($kamar->tgl_masuk, $kamar->jam_masuk);
            $waktuKeluar = $this->gabungTanggalJam($data['tgl_keluar'], $data['jam_keluar']);
            $lama = $this->hitungLamaInap($waktuMasuk, $waktuKeluar);

            DB::table('kamar_inap')
                ->where('no_rawat', $noRawat)
                ->where('tgl_masuk', $kamar->tgl_masuk)
                ->where('jam_masuk', $kamar->jam_masuk)
                ->update([
                    'diagnosa_akhir' => $data['diagnosa_akhir'],
                    'tgl_keluar' => $data['tgl_keluar'],
                    'jam_keluar' => $this->normalisasiJam($data['jam_keluar']),
                    'lama' => $lama,
                    'stts_pulang' => $data['stts_pulang'],
                    'ttl_biaya' => $lama * (float) $kamar->trf_kamar,
                ]);

            DB::table('kamar')
                ->where('kd_kamar', $kamar->kd_kamar)
                ->update(['status' => $data['stts_pulang'] === '-' ? 'ISI' : 'KOSONG']);
        });
    }

    private function gabungTanggalJam(string $tanggal, string $jam): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $tanggal.' '.$this->normalisasiJam($jam));
    }

    private function normalisasiJam(string $jam): string
    {
        return strlen($jam) === 5 ? "{$jam}:00" : $jam;
    }

    private function hitungLamaInap(CarbonImmutable $waktuMasuk, CarbonImmutable $waktuKeluar): int
    {
        return max(1, (int) ceil($waktuMasuk->diffInHours($waktuKeluar) / 24));
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

    private function queryRegistrasiDasar(): Builder
    {
        return DB::table('reg_periksa')
            ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
            ->join('dokter', 'reg_periksa.kd_dokter', '=', 'dokter.kd_dokter')
            ->join('poliklinik', 'reg_periksa.kd_poli', '=', 'poliklinik.kd_poli')
            ->join('penjab', 'reg_periksa.kd_pj', '=', 'penjab.kd_pj')
            ->leftJoin('bridging_sep', function ($join): void {
                $join->on('bridging_sep.no_rawat', '=', 'reg_periksa.no_rawat')
                    ->where('bridging_sep.jnspelayanan', '2');
            })
            ->select([
                'reg_periksa.no_reg',
                'reg_periksa.no_rawat',
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.kd_dokter',
                'reg_periksa.no_rkm_medis',
                'reg_periksa.kd_poli',
                'reg_periksa.kd_pj',
                'reg_periksa.stts',
                'reg_periksa.stts_daftar',
                'reg_periksa.status_lanjut',
                'reg_periksa.status_bayar',
                'pasien.nm_pasien',
                'pasien.jk',
                'pasien.tgl_lahir',
                'pasien.no_peserta',
                'pasien.no_ktp',
                'pasien.no_tlp',
                'dokter.nm_dokter',
                'poliklinik.nm_poli',
                'penjab.png_jawab',
                'bridging_sep.no_sep',
                'bridging_sep.tglsep',
                'bridging_sep.klsrawat',
                'bridging_sep.diagawal',
                'bridging_sep.jam_sep',
                'bridging_sep.nmdiagnosaawal',
                DB::raw("CONCAT(reg_periksa.umurdaftar, ' ', reg_periksa.sttsumur) as umur_registrasi"),
                DB::raw('(SELECT diagnosa_pasien.kd_penyakit FROM diagnosa_pasien WHERE diagnosa_pasien.no_rawat = reg_periksa.no_rawat AND diagnosa_pasien.status = "Ralan" ORDER BY diagnosa_pasien.prioritas ASC LIMIT 1) as kd_penyakit_igd'),
                DB::raw('(SELECT penyakit.nm_penyakit FROM diagnosa_pasien INNER JOIN penyakit ON penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit WHERE diagnosa_pasien.no_rawat = reg_periksa.no_rawat AND diagnosa_pasien.status = "Ralan" ORDER BY diagnosa_pasien.prioritas ASC LIMIT 1) as nm_penyakit_igd'),
                DB::raw('EXISTS(SELECT 1 FROM bridging_sep as sep WHERE sep.no_rawat = reg_periksa.no_rawat AND sep.jnspelayanan = "2") as ada_sep'),
                DB::raw('EXISTS(SELECT 1 FROM pemeriksaan_ralan WHERE pemeriksaan_ralan.no_rawat = reg_periksa.no_rawat) as ada_cppt'),
                DB::raw('EXISTS(SELECT 1 FROM uxui_tindakan_pasien WHERE uxui_tindakan_pasien.no_rawat = reg_periksa.no_rawat AND uxui_tindakan_pasien.no_rkm_medis = reg_periksa.no_rkm_medis AND uxui_tindakan_pasien.type_akses IN ("rj", "igd") AND uxui_tindakan_pasien.pemeriksaan = "1") as sudah_diperiksa_dokter'),
                DB::raw('EXISTS(SELECT 1 FROM uxui_tindakan_pasien_perawat WHERE uxui_tindakan_pasien_perawat.no_rawat = reg_periksa.no_rawat AND uxui_tindakan_pasien_perawat.no_rkm_medis = reg_periksa.no_rkm_medis AND uxui_tindakan_pasien_perawat.type_akses IN ("rj", "igd") AND uxui_tindakan_pasien_perawat.pemeriksaan = "1") as sudah_diperiksa_perawat'),
                DB::raw('EXISTS(SELECT 1 FROM permintaan_lab WHERE permintaan_lab.no_rawat = reg_periksa.no_rawat AND permintaan_lab.status = "ralan") as ada_lab'),
                DB::raw('EXISTS(SELECT 1 FROM permintaan_radiologi WHERE permintaan_radiologi.no_rawat = reg_periksa.no_rawat AND permintaan_radiologi.status = "ralan") as ada_radiologi'),
                DB::raw('EXISTS(SELECT 1 FROM uxui_permintaan_fisioterapi WHERE uxui_permintaan_fisioterapi.no_rawat = reg_periksa.no_rawat AND uxui_permintaan_fisioterapi.status = "ralan") as ada_fisioterapi'),
                DB::raw('EXISTS(SELECT 1 FROM resep_obat WHERE resep_obat.no_rawat = reg_periksa.no_rawat AND resep_obat.status = "ralan") as ada_resep'),
                DB::raw('(
                    EXISTS(SELECT 1 FROM rawat_jl_dr WHERE rawat_jl_dr.no_rawat = reg_periksa.no_rawat)
                    OR EXISTS(SELECT 1 FROM rawat_jl_pr WHERE rawat_jl_pr.no_rawat = reg_periksa.no_rawat)
                    OR EXISTS(SELECT 1 FROM rawat_jl_drpr WHERE rawat_jl_drpr.no_rawat = reg_periksa.no_rawat)
                ) as ada_tindakan'),
                DB::raw('EXISTS(SELECT 1 FROM resume_pasien WHERE resume_pasien.no_rawat = reg_periksa.no_rawat) as ada_resume'),
                DB::raw('EXISTS(SELECT 1 FROM kamar_inap WHERE kamar_inap.no_rawat = reg_periksa.no_rawat) as is_ranap'),
            ]);
    }

    private function filterPencarianRegistrasi(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            $query->where('reg_periksa.no_rawat', 'like', "%{$search}%")
                ->orWhere('reg_periksa.no_rkm_medis', 'like', "%{$search}%")
                ->orWhere('pasien.nm_pasien', 'like', "%{$search}%")
                ->orWhere('pasien.no_ktp', 'like', "%{$search}%")
                ->orWhere('pasien.no_peserta', 'like', "%{$search}%")
                ->orWhere('dokter.nm_dokter', 'like', "%{$search}%")
                ->orWhere('poliklinik.nm_poli', 'like', "%{$search}%")
                ->orWhere('penjab.png_jawab', 'like', "%{$search}%")
                ->orWhere('reg_periksa.stts', 'like', "%{$search}%")
                ->orWhere('bridging_sep.no_sep', 'like', "%{$search}%")
                ->orWhere('bridging_sep.nmdiagnosaawal', 'like', "%{$search}%")
                ->orWhereExists(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->selectRaw('1')
                        ->from('diagnosa_pasien')
                        ->join('penyakit', 'penyakit.kd_penyakit', '=', 'diagnosa_pasien.kd_penyakit')
                        ->whereColumn('diagnosa_pasien.no_rawat', 'reg_periksa.no_rawat')
                        ->where('diagnosa_pasien.status', 'Ralan')
                        ->where(function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->where('diagnosa_pasien.kd_penyakit', 'like', "{$search}%")
                                ->orWhere('penyakit.nm_penyakit', 'like', "%{$search}%");
                        });
                });
        });
    }

    private function filterPencarianRawatInap(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            $query->where('reg_periksa.no_rawat', 'like', "%{$search}%")
                ->orWhere('reg_periksa.no_rkm_medis', 'like', "%{$search}%")
                ->orWhere('pasien.nm_pasien', 'like', "%{$search}%")
                ->orWhere('dokter.nm_dokter', 'like', "%{$search}%")
                ->orWhere('kamar_inap.kd_kamar', 'like', "%{$search}%")
                ->orWhere('bangsal.nm_bangsal', 'like', "%{$search}%")
                ->orWhere('kamar.kelas', 'like', "%{$search}%")
                ->orWhere('bridging_sep.no_sep', 'like', "%{$search}%")
                ->orWhere('bridging_sep.nmdiagnosaawal', 'like', "%{$search}%")
                ->orWhere('kamar_inap.diagnosa_awal', 'like', "%{$search}%")
                ->orWhere('kamar_inap.diagnosa_akhir', 'like', "%{$search}%")
                ->orWhere('penjab.png_jawab', 'like', "%{$search}%")
                ->orWhereExists(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->selectRaw('1')
                        ->from('dpjp_ranap')
                        ->join('dokter as dokter_dpjp', 'dokter_dpjp.kd_dokter', '=', 'dpjp_ranap.kd_dokter')
                        ->whereColumn('dpjp_ranap.no_rawat', 'reg_periksa.no_rawat')
                        ->where('dokter_dpjp.nm_dokter', 'like', "%{$search}%");
                });
        });
    }

    /**
     * @param  array{tgl_awal:string,tgl_akhir:string,tipe_filter_ranap?:string|null}  $filter
     */
    private function filterWaktuRawatInap(Builder $query, array $filter): Builder
    {
        $tipeFilter = $filter['tipe_filter_ranap'] ?? 'belum_pulang';

        if ($tipeFilter === 'tanggal_masuk') {
            return $this->filterRiwayatKamarRawatInap($query, 'tgl_masuk', $filter['tgl_awal'], $filter['tgl_akhir']);
        }

        if ($tipeFilter === 'tanggal_keluar') {
            return $this->filterRiwayatKamarRawatInap($query, 'tgl_keluar', $filter['tgl_awal'], $filter['tgl_akhir']);
        }

        return $query->where('kamar_inap.stts_pulang', '-');
    }

    private function filterRiwayatKamarRawatInap(Builder $query, string $tanggalColumn, string $tanggalAwal, string $tanggalAkhir): Builder
    {
        return $query->whereExists(function (Builder $subQuery) use ($tanggalColumn, $tanggalAwal, $tanggalAkhir): void {
            $subQuery
                ->selectRaw('1')
                ->from('kamar_inap as kamar_filter')
                ->whereColumn('kamar_filter.no_rawat', 'reg_periksa.no_rawat')
                ->whereBetween("kamar_filter.{$tanggalColumn}", [$tanggalAwal, $tanggalAkhir]);
        });
    }

    private function filterPencarianRujukanInternal(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search): void {
            $query->where('reg_periksa.no_rawat', 'like', "%{$search}%")
                ->orWhere('reg_periksa.no_rkm_medis', 'like', "%{$search}%")
                ->orWhere('pasien.nm_pasien', 'like', "%{$search}%")
                ->orWhere('dokter_awal.nm_dokter', 'like', "%{$search}%")
                ->orWhere('poli_awal.nm_poli', 'like', "%{$search}%")
                ->orWhere('dokter_tujuan.nm_dokter', 'like', "%{$search}%")
                ->orWhere('poli_tujuan.nm_poli', 'like', "%{$search}%")
                ->orWhere('penjab.png_jawab', 'like', "%{$search}%");
        });
    }
}
