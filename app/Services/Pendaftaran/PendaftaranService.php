<?php

namespace App\Services\Pendaftaran;

use App\Modules\Bpjs\Services\VClaimService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PendaftaranService
{
    /**
     * @var list<string>
     */
    private array $tabelPendukungYangDihapus = [
        'rujuk_masuk',
        'referensi_mobilejkn_bpjs_taskid',
        'referensi_mobilejkn_bpjs',
        'uxui_list_panggil_monitor',
        'uxui_list_panggil_monitor_poli',
        'temppanggilnorawat',
        'antripoli',
    ];

    public function __construct(private readonly VClaimService $vclaimService) {}

    /**
     * @param  array{
     *     jenis_pendaftaran:string,
     *     no_rkm_medis:string,
     *     tgl_registrasi:string,
     *     jam_reg:string,
     *     kd_dokter:string,
     *     kd_poli?:string|null,
     *     kd_pj?:string|null,
     *     p_jawab?:string|null,
     *     almt_pj?:string|null,
     *     hubunganpj?:string|null,
     *     perujuk?:string|null,
     *     kategori_rujuk?:string|null
     * }  $data
     * @return array{no_rawat:string,no_reg:string}
     */
    public function simpan(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $patient = DB::table('pasien')
                ->where('no_rkm_medis', $data['no_rkm_medis'])
                ->first();

            if (! $patient) {
                throw ValidationException::withMessages([
                    'no_rkm_medis' => 'Pasien tidak ditemukan.',
                ]);
            }

            $clinicCode = $data['jenis_pendaftaran'] === 'igd' ? 'IGDK' : $data['kd_poli'];
            $registrationDate = CarbonImmutable::parse($data['tgl_registrasi']);
            $paymentCode = $this->resolvedPaymentCode($data, $patient);
            $responsiblePerson = $this->responsiblePersonData($data, $patient);

            $alreadyRegisteredToday = DB::table('reg_periksa')
                ->where('no_rkm_medis', $data['no_rkm_medis'])
                ->whereDate('tgl_registrasi', $registrationDate->toDateString())
                ->exists();

            if ($alreadyRegisteredToday) {
                throw ValidationException::withMessages([
                    'no_rkm_medis' => 'Pasien sudah terdaftar hari ini.',
                ]);
            }

            $clinic = DB::table('poliklinik')
                ->where('kd_poli', $clinicCode)
                ->where('status', '1')
                ->first();

            if (! $clinic) {
                throw ValidationException::withMessages([
                    'kd_poli' => 'Poliklinik tidak aktif atau tidak ditemukan.',
                ]);
            }

            $doctorExists = DB::table('dokter')
                ->where('kd_dokter', $data['kd_dokter'])
                ->where('status', '1')
                ->exists();

            if (! $doctorExists) {
                throw ValidationException::withMessages([
                    'kd_dokter' => 'Dokter tidak aktif atau tidak ditemukan.',
                ]);
            }

            $paymentExists = DB::table('penjab')
                ->where('kd_pj', $paymentCode)
                ->where('status', '1')
                ->exists();

            if (! $paymentExists) {
                throw ValidationException::withMessages([
                    'kd_pj' => 'Jenis bayar tidak aktif atau tidak ditemukan.',
                ]);
            }

            $registrationStatus = DB::table('reg_periksa')
                ->where('no_rkm_medis', $data['no_rkm_medis'])
                ->exists() ? 'Lama' : 'Baru';

            $clinicStatus = DB::table('reg_periksa')
                ->where('no_rkm_medis', $data['no_rkm_medis'])
                ->where('kd_poli', $clinicCode)
                ->exists() ? 'Lama' : 'Baru';

            $age = $this->calculateRegistrationAge((string) $patient->tgl_lahir, $registrationDate);
            $noReg = $this->nextRegistrationNumber($data['kd_dokter'], $registrationDate);
            $noRawat = $this->nextCareNumber($registrationDate);
            $registrationFee = $registrationStatus === 'Lama'
                ? (float) $clinic->registrasilama
                : (float) $clinic->registrasi;

            DB::table('reg_periksa')->insert([
                'no_reg' => $noReg,
                'no_rawat' => $noRawat,
                'tgl_registrasi' => $registrationDate->toDateString(),
                'jam_reg' => $this->normalizeTime($data['jam_reg']),
                'kd_dokter' => $data['kd_dokter'],
                'no_rkm_medis' => $data['no_rkm_medis'],
                'kd_poli' => $clinicCode,
                'p_jawab' => $responsiblePerson['name'],
                'almt_pj' => $responsiblePerson['address'],
                'hubunganpj' => $responsiblePerson['relationship'],
                'biaya_reg' => $registrationFee,
                'stts' => 'Belum',
                'stts_daftar' => $registrationStatus,
                'status_lanjut' => 'Ralan',
                'kd_pj' => $paymentCode,
                'umurdaftar' => $age['value'],
                'sttsumur' => $age['unit'],
                'status_bayar' => 'Belum Bayar',
                'status_poli' => $clinicStatus,
            ]);

            DB::table('rujuk_masuk')->insert([
                'no_rawat' => $noRawat,
                'perujuk' => filled($data['perujuk'] ?? null) ? $data['perujuk'] : '-',
                'alamat' => '-',
                'no_rujuk' => '-',
                'jm_perujuk' => 0,
                'dokter_perujuk' => filled($data['perujuk'] ?? null) ? $data['perujuk'] : '-',
                'kd_penyakit' => '-',
                'kategori_rujuk' => filled($data['kategori_rujuk'] ?? null) ? $data['kategori_rujuk'] : '-',
                'keterangan' => '-',
                'no_balasan' => '-',
            ]);

            return [
                'no_rawat' => $noRawat,
                'no_reg' => $noReg,
            ];
        });
    }

    /**
     * @param  array{
     *     jenis_pendaftaran:string,
     *     tgl_registrasi:string,
     *     jam_reg:string,
     *     kd_dokter:string,
     *     kd_poli?:string|null,
     *     kd_pj:string,
     *     p_jawab:string,
     *     almt_pj:string,
     *     hubunganpj:string,
     *     perujuk?:string|null,
     *     kategori_rujuk?:string|null
     * }  $data
     * @return array{berhasil: bool, pesan: string}
     */
    public function ubah(string $noRawat, array $data): array
    {
        return DB::transaction(function () use ($noRawat, $data): array {
            $pendaftaran = DB::table('reg_periksa')
                ->where('no_rawat', $noRawat)
                ->first();

            if ($pendaftaran === null) {
                return [
                    'berhasil' => false,
                    'pesan' => "Pendaftaran {$noRawat} tidak ditemukan.",
                ];
            }

            $patient = DB::table('pasien')
                ->where('no_rkm_medis', $pendaftaran->no_rkm_medis)
                ->first();

            if ($patient === null) {
                throw ValidationException::withMessages([
                    'no_rkm_medis' => 'Data pasien untuk pendaftaran ini tidak ditemukan.',
                ]);
            }

            $clinicCode = $data['jenis_pendaftaran'] === 'igd' ? 'IGDK' : $data['kd_poli'];
            $registrationDate = CarbonImmutable::parse($data['tgl_registrasi']);

            $clinic = DB::table('poliklinik')
                ->where('kd_poli', $clinicCode)
                ->where('status', '1')
                ->first();

            if (! $clinic) {
                throw ValidationException::withMessages([
                    'kd_poli' => 'Poliklinik tidak aktif atau tidak ditemukan.',
                ]);
            }

            $doctorExists = DB::table('dokter')
                ->where('kd_dokter', $data['kd_dokter'])
                ->where('status', '1')
                ->exists();

            if (! $doctorExists) {
                throw ValidationException::withMessages([
                    'kd_dokter' => 'Dokter tidak aktif atau tidak ditemukan.',
                ]);
            }

            $paymentExists = DB::table('penjab')
                ->where('kd_pj', $data['kd_pj'])
                ->where('status', '1')
                ->exists();

            if (! $paymentExists) {
                throw ValidationException::withMessages([
                    'kd_pj' => 'Jenis bayar tidak aktif atau tidak ditemukan.',
                ]);
            }

            $doctorChanged = $pendaftaran->kd_dokter !== $data['kd_dokter'];
            $dateChanged = $pendaftaran->tgl_registrasi !== $registrationDate->toDateString();
            $noReg = ($doctorChanged || $dateChanged)
                ? $this->nextRegistrationNumber($data['kd_dokter'], $registrationDate, $noRawat)
                : (string) $pendaftaran->no_reg;

            $clinicStatus = DB::table('reg_periksa')
                ->where('no_rkm_medis', $pendaftaran->no_rkm_medis)
                ->where('kd_poli', $clinicCode)
                ->where('no_rawat', '!=', $noRawat)
                ->exists() ? 'Lama' : 'Baru';

            $age = $this->calculateRegistrationAge((string) $patient->tgl_lahir, $registrationDate);
            $registrationFee = $pendaftaran->stts_daftar === 'Lama'
                ? (float) $clinic->registrasilama
                : (float) $clinic->registrasi;

            DB::table('reg_periksa')
                ->where('no_rawat', $noRawat)
                ->update([
                    'no_reg' => $noReg,
                    'tgl_registrasi' => $registrationDate->toDateString(),
                    'jam_reg' => $this->normalizeTime($data['jam_reg']),
                    'kd_dokter' => $data['kd_dokter'],
                    'kd_poli' => $clinicCode,
                    'p_jawab' => $data['p_jawab'],
                    'almt_pj' => $data['almt_pj'],
                    'hubunganpj' => $data['hubunganpj'],
                    'biaya_reg' => $registrationFee,
                    'kd_pj' => $data['kd_pj'],
                    'umurdaftar' => $age['value'],
                    'sttsumur' => $age['unit'],
                    'status_poli' => $clinicStatus,
                ]);

            DB::table('rujuk_masuk')->updateOrInsert(
                ['no_rawat' => $noRawat],
                [
                    'perujuk' => filled($data['perujuk'] ?? null) ? $data['perujuk'] : '-',
                    'alamat' => '-',
                    'no_rujuk' => '-',
                    'jm_perujuk' => 0,
                    'dokter_perujuk' => filled($data['perujuk'] ?? null) ? $data['perujuk'] : '-',
                    'kd_penyakit' => '-',
                    'kategori_rujuk' => filled($data['kategori_rujuk'] ?? null) ? $data['kategori_rujuk'] : '-',
                    'keterangan' => '-',
                    'no_balasan' => '-',
                ],
            );

            return [
                'berhasil' => true,
                'pesan' => "Pendaftaran {$noRawat} berhasil diperbarui.",
            ];
        });
    }

    /**
     * @param  array{
     *     kd_poli:string,
     *     kd_dokter:string
     * }  $data
     * @return array{berhasil: bool, pesan: string}
     */
    public function simpanRujukanInternal(string $noRawat, array $data): array
    {
        return DB::transaction(function () use ($noRawat, $data): array {
            $pendaftaran = DB::table('reg_periksa')
                ->where('no_rawat', $noRawat)
                ->first(['no_rawat', 'kd_poli', 'kd_dokter', 'status_lanjut']);

            if ($pendaftaran === null) {
                return [
                    'berhasil' => false,
                    'pesan' => "Pendaftaran {$noRawat} tidak ditemukan.",
                ];
            }

            if ($pendaftaran->status_lanjut !== 'Ralan') {
                return [
                    'berhasil' => false,
                    'pesan' => "Rujukan internal untuk {$noRawat} hanya tersedia pada pasien rawat jalan.",
                ];
            }

            $clinicExists = DB::table('poliklinik')
                ->where('kd_poli', $data['kd_poli'])
                ->where('status', '1')
                ->exists();

            if (! $clinicExists) {
                throw ValidationException::withMessages([
                    'kd_poli' => 'Poliklinik tujuan tidak aktif atau tidak ditemukan.',
                ]);
            }

            $doctorExists = DB::table('dokter')
                ->where('kd_dokter', $data['kd_dokter'])
                ->where('status', '1')
                ->exists();

            if (! $doctorExists) {
                throw ValidationException::withMessages([
                    'kd_dokter' => 'Dokter tujuan tidak aktif atau tidak ditemukan.',
                ]);
            }

            if ($pendaftaran->kd_poli === $data['kd_poli'] && $pendaftaran->kd_dokter === $data['kd_dokter']) {
                return [
                    'berhasil' => false,
                    'pesan' => 'Dokter dan poliklinik tujuan masih sama dengan poli asal.',
                ];
            }

            $alreadyReferred = DB::table('rujukan_internal_poli')
                ->where('no_rawat', $noRawat)
                ->where('kd_dokter', $data['kd_dokter'])
                ->exists();

            if ($alreadyReferred) {
                return [
                    'berhasil' => false,
                    'pesan' => 'Rujukan internal ke dokter tersebut sudah pernah dibuat untuk pendaftaran ini.',
                ];
            }

            DB::table('rujukan_internal_poli')->insert([
                'no_rawat' => $noRawat,
                'kd_dokter' => $data['kd_dokter'],
                'kd_poli' => $data['kd_poli'],
            ]);

            return [
                'berhasil' => true,
                'pesan' => "Rujukan internal untuk {$noRawat} berhasil disimpan.",
            ];
        });
    }

    /**
     * @param  array{
     *     kd_kamar:string,
     *     kd_dokter:string,
     *     tgl_masuk:string,
     *     jam_masuk:string,
     *     diagnosa_awal:string,
     *     kd_penyakit?:string|null,
     *     mode_diagnosa:string
     * }  $data
     * @return array{berhasil: bool, pesan: string}
     */
    public function simpanPindahRawatInap(string $noRawat, array $data): array
    {
        return DB::transaction(function () use ($noRawat, $data): array {
            $pendaftaran = DB::table('reg_periksa')
                ->where('no_rawat', $noRawat)
                ->lockForUpdate()
                ->first(['no_rawat', 'status_lanjut']);

            if ($pendaftaran === null) {
                return [
                    'berhasil' => false,
                    'pesan' => "Pendaftaran {$noRawat} tidak ditemukan.",
                ];
            }

            if ($pendaftaran->status_lanjut === 'Ranap') {
                return [
                    'berhasil' => false,
                    'pesan' => "Pasien {$noRawat} sudah berstatus rawat inap.",
                ];
            }

            if (DB::table('kamar_inap')->where('no_rawat', $noRawat)->where('stts_pulang', '-')->exists()) {
                return [
                    'berhasil' => false,
                    'pesan' => "Pasien {$noRawat} sudah memiliki data kamar rawat inap aktif.",
                ];
            }

            $doctorExists = DB::table('dokter')
                ->where('kd_dokter', $data['kd_dokter'])
                ->where('status', '1')
                ->exists();

            if (! $doctorExists) {
                throw ValidationException::withMessages([
                    'kd_dokter' => 'Dokter DPJP tidak aktif atau tidak ditemukan.',
                ]);
            }

            $room = DB::table('kamar')
                ->where('kd_kamar', $data['kd_kamar'])
                ->lockForUpdate()
                ->first(['kd_kamar', 'trf_kamar', 'status', 'statusdata']);

            if ($room === null || $room->statusdata !== '1') {
                throw ValidationException::withMessages([
                    'kd_kamar' => 'Kamar tidak aktif atau tidak ditemukan.',
                ]);
            }

            if ($room->status !== 'KOSONG') {
                throw ValidationException::withMessages([
                    'kd_kamar' => "Kamar {$data['kd_kamar']} tidak kosong. Pilih kamar lain.",
                ]);
            }

            $diagnosis = $this->diagnosaMasuk($data);

            DB::table('kamar_inap')->insert([
                'no_rawat' => $noRawat,
                'kd_kamar' => $data['kd_kamar'],
                'trf_kamar' => (float) $room->trf_kamar,
                'diagnosa_awal' => $diagnosis,
                'diagnosa_akhir' => '-',
                'tgl_masuk' => CarbonImmutable::parse($data['tgl_masuk'])->toDateString(),
                'jam_masuk' => $this->normalizeTime($data['jam_masuk']),
                'tgl_keluar' => null,
                'jam_keluar' => null,
                'lama' => 0,
                'ttl_biaya' => 0,
                'stts_pulang' => '-',
            ]);

            DB::table('dpjp_ranap')->updateOrInsert(
                [
                    'no_rawat' => $noRawat,
                    'kd_dokter' => $data['kd_dokter'],
                ],
                [
                    'no_rawat' => $noRawat,
                    'kd_dokter' => $data['kd_dokter'],
                ],
            );

            DB::table('reg_periksa')
                ->where('no_rawat', $noRawat)
                ->update(['status_lanjut' => 'Ranap']);

            DB::table('kamar')
                ->where('kd_kamar', $data['kd_kamar'])
                ->update(['status' => 'ISI']);

            return [
                'berhasil' => true,
                'pesan' => "Pasien {$noRawat} berhasil dipindahkan ke rawat inap.",
            ];
        });
    }

    /**
     * @return array{berhasil: bool, pesan: string}
     */
    public function batal(string $noRawat, string $user): array
    {
        $pendaftaran = DB::table('reg_periksa')
            ->where('no_rawat', $noRawat)
            ->first(['no_rawat', 'stts']);

        if ($pendaftaran === null) {
            return [
                'berhasil' => false,
                'pesan' => "Pendaftaran {$noRawat} tidak ditemukan.",
            ];
        }

        if ($pendaftaran->stts === 'Batal') {
            DB::transaction(function () use ($noRawat): void {
                DB::table('reg_periksa')
                    ->where('no_rawat', $noRawat)
                    ->update(['stts' => 'Belum']);

                DB::table('referensi_mobilejkn_bpjs')
                    ->where('no_rawat', $noRawat)
                    ->update([
                        'status' => 'Belum',
                        'validasi' => now(),
                    ]);
            });

            return [
                'berhasil' => true,
                'pesan' => "Pendaftaran {$noRawat} berhasil diaktifkan kembali.",
            ];
        }

        $sepResult = $this->hapusSepJikaAda($noRawat, $user, 'dibatalkan');

        if (! $sepResult['berhasil']) {
            return $sepResult;
        }

        DB::transaction(function () use ($noRawat): void {
            DB::table('reg_periksa')
                ->where('no_rawat', $noRawat)
                ->update(['stts' => 'Batal']);

            DB::table('referensi_mobilejkn_bpjs')
                ->where('no_rawat', $noRawat)
                ->update([
                    'status' => 'Batal',
                    'validasi' => now(),
                ]);
        });

        return [
            'berhasil' => true,
            'pesan' => "Pendaftaran {$noRawat} berhasil dibatalkan.",
        ];
    }

    /**
     * @return array{berhasil: bool, pesan: string}
     */
    public function hapus(string $noRawat, string $user): array
    {
        $pendaftaran = DB::table('reg_periksa')
            ->where('no_rawat', $noRawat)
            ->first(['no_rawat']);

        if ($pendaftaran === null) {
            return [
                'berhasil' => false,
                'pesan' => "Pendaftaran {$noRawat} tidak ditemukan.",
            ];
        }

        $sepResult = $this->hapusSepJikaAda($noRawat, $user, 'dihapus');

        if (! $sepResult['berhasil']) {
            return $sepResult;
        }

        $pemakai = $this->pemakaiNoRawat($noRawat);

        if ($pemakai !== []) {
            return [
                'berhasil' => false,
                'pesan' => 'Pendaftaran tidak dapat dihapus karena sudah dipakai di '.implode(', ', $pemakai).'. Batalkan atau hapus data terkait terlebih dahulu.',
            ];
        }

        try {
            DB::transaction(function () use ($noRawat): void {
                foreach ($this->tabelPendukungYangDihapus as $table) {
                    DB::table($table)->where('no_rawat', $noRawat)->delete();
                }

                $deleted = DB::table('reg_periksa')->where('no_rawat', $noRawat)->delete();

                if ($deleted < 1) {
                    throw new \RuntimeException('Data pendaftaran tidak berubah saat proses hapus.');
                }
            });
        } catch (QueryException $exception) {
            report($exception);

            return [
                'berhasil' => false,
                'pesan' => 'Pendaftaran gagal dihapus karena masih terhubung dengan data pelayanan lain. Periksa CPPT, penilaian, tindakan, resep, billing, atau data klinis terkait.',
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'berhasil' => false,
                'pesan' => $exception->getMessage(),
            ];
        }

        return [
            'berhasil' => true,
            'pesan' => "Pendaftaran {$noRawat} berhasil dihapus.",
        ];
    }

    public function nomorRekamMedisBerikutnya(): string
    {
        $baseNumber = DB::table('set_no_rkm_medis')->value('no_rkm_medis')
            ?: DB::table('pasien')->whereRaw("no_rkm_medis REGEXP '^[0-9]+$'")->max('no_rkm_medis')
            ?: '0';

        return str_pad((string) (((int) $baseNumber) + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, list<array{value:string,label:string}>>
     */
    public function referensiFormDataPasien(): array
    {
        return [
            'payments' => $this->paymentOptions(),
            'physicalDisabilities' => DB::table('cacat_fisik')
                ->select('id', 'nama_cacat')
                ->orderBy('nama_cacat')
                ->get()
                ->map(fn (object $row): array => ['value' => (string) $row->id, 'label' => (string) $row->nama_cacat])
                ->all(),
            'ethnicities' => DB::table('suku_bangsa')
                ->select('id', 'nama_suku_bangsa')
                ->orderBy('nama_suku_bangsa')
                ->get()
                ->map(fn (object $row): array => ['value' => (string) $row->id, 'label' => (string) $row->nama_suku_bangsa])
                ->all(),
            'languages' => DB::table('bahasa_pasien')
                ->select('id', 'nama_bahasa')
                ->orderBy('nama_bahasa')
                ->get()
                ->map(fn (object $row): array => ['value' => (string) $row->id, 'label' => (string) $row->nama_bahasa])
                ->all(),
            'companies' => DB::table('perusahaan_pasien')
                ->select('kode_perusahaan', 'nama_perusahaan')
                ->orderBy('nama_perusahaan')
                ->get()
                ->map(fn (object $row): array => ['value' => (string) $row->kode_perusahaan, 'label' => (string) $row->nama_perusahaan])
                ->all(),
        ];
    }

    /**
     * @return list<array{value:string,label:string,description:string}>
     */
    public function referensiWilayah(string $jenis, string $query): array
    {
        $search = trim($query);

        return match ($jenis) {
            'kelurahan' => $this->searchWilayah('kelurahan', 'kd_kel', 'nm_kel', $search),
            'kecamatan' => $this->searchWilayah('kecamatan', 'kd_kec', 'nm_kec', $search),
            'kabupaten' => $this->searchWilayah('kabupaten', 'kd_kab', 'nm_kab', $search),
            'propinsi' => $this->searchWilayah('propinsi', 'kd_prop', 'nm_prop', $search),
            default => [],
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detailDataPasien(string $noRkmMedis): ?array
    {
        $patient = DB::table('pasien')
            ->where('no_rkm_medis', $noRkmMedis)
            ->first();

        return $patient === null ? null : (array) $patient;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{no_rkm_medis:string}
     */
    public function simpanDataPasien(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $noRkmMedis = ((bool) $data['auto_no_rm'])
                ? $this->nomorRekamMedisBerikutnya()
                : (string) $data['no_rkm_medis'];

            if (DB::table('pasien')->where('no_rkm_medis', $noRkmMedis)->exists()) {
                throw ValidationException::withMessages([
                    'no_rkm_medis' => "No RM {$noRkmMedis} sudah digunakan.",
                ]);
            }

            DB::table('pasien')->insert([
                ...$this->payloadDataPasien($data),
                'no_rkm_medis' => $noRkmMedis,
                'tgl_daftar' => now()->toDateString(),
                'nip' => '-',
            ]);

            if ((bool) $data['auto_no_rm']) {
                DB::table('set_no_rkm_medis')->update(['no_rkm_medis' => $noRkmMedis]);
            }

            return ['no_rkm_medis' => $noRkmMedis];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{no_rkm_medis:string}
     */
    public function ubahDataPasien(string $noRkmMedis, array $data): array
    {
        return DB::transaction(function () use ($noRkmMedis, $data): array {
            $patient = DB::table('pasien')
                ->where('no_rkm_medis', $noRkmMedis)
                ->lockForUpdate()
                ->first(['no_rkm_medis']);

            if ($patient === null) {
                throw ValidationException::withMessages([
                    'no_rkm_medis' => "Pasien {$noRkmMedis} tidak ditemukan.",
                ]);
            }

            $newNoRkmMedis = (string) $data['no_rkm_medis'];

            DB::table('pasien')
                ->where('no_rkm_medis', $noRkmMedis)
                ->update([
                    ...$this->payloadDataPasien($data),
                    'no_rkm_medis' => $newNoRkmMedis,
                ]);

            if ($newNoRkmMedis !== $noRkmMedis) {
                DB::table('reg_periksa')
                    ->where('no_rkm_medis', $noRkmMedis)
                    ->update(['no_rkm_medis' => $newNoRkmMedis]);
            }

            return ['no_rkm_medis' => $newNoRkmMedis];
        });
    }

    /**
     * @return array{berhasil: bool, pesan: string}
     */
    public function hapusDataPasien(string $noRkmMedis): array
    {
        if (! DB::table('pasien')->where('no_rkm_medis', $noRkmMedis)->exists()) {
            return [
                'berhasil' => false,
                'pesan' => "Pasien {$noRkmMedis} tidak ditemukan.",
            ];
        }

        if (DB::table('reg_periksa')->where('no_rkm_medis', $noRkmMedis)->exists()) {
            return [
                'berhasil' => false,
                'pesan' => "Pasien {$noRkmMedis} tidak dapat dihapus karena sudah memiliki riwayat pendaftaran. Gunakan Gabung RM bila data pasien duplikat.",
            ];
        }

        try {
            DB::table('pasien')->where('no_rkm_medis', $noRkmMedis)->delete();
        } catch (QueryException $exception) {
            report($exception);

            return [
                'berhasil' => false,
                'pesan' => 'Pasien gagal dihapus karena masih terhubung dengan data lain di rekam medis.',
            ];
        }

        return [
            'berhasil' => true,
            'pesan' => "Pasien {$noRkmMedis} berhasil dihapus.",
        ];
    }

    /**
     * @param  array{no_rm_lama:string,no_rm_baru:string}  $data
     * @return array{berhasil: bool, pesan: string}
     */
    public function gabungRekamMedis(array $data): array
    {
        $noRmLama = $data['no_rm_lama'];
        $noRmBaru = $data['no_rm_baru'];

        if ($noRmLama === $noRmBaru) {
            return [
                'berhasil' => false,
                'pesan' => 'No RM lama dan No RM baru harus berbeda.',
            ];
        }

        if (! DB::table('reg_periksa')->where('no_rkm_medis', $noRmLama)->exists()) {
            return [
                'berhasil' => false,
                'pesan' => "Gagal menggabungkan data. Pasien {$noRmLama} tidak memiliki data rekam medis yang bisa dipindahkan.",
            ];
        }

        try {
            DB::transaction(function () use ($noRmLama, $noRmBaru): void {
                DB::table('reg_periksa')
                    ->where('no_rkm_medis', $noRmLama)
                    ->update(['no_rkm_medis' => $noRmBaru]);

                $deleted = DB::table('pasien')
                    ->where('no_rkm_medis', $noRmLama)
                    ->delete();

                if ($deleted < 1) {
                    throw new \RuntimeException("RM lama {$noRmLama} gagal dihapus setelah riwayat dipindahkan.");
                }
            });
        } catch (QueryException $exception) {
            report($exception);

            return [
                'berhasil' => false,
                'pesan' => "Gabung RM gagal. RM {$noRmLama} masih terhubung dengan data lain selain pendaftaran.",
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'berhasil' => false,
                'pesan' => $exception->getMessage(),
            ];
        }

        return [
            'berhasil' => true,
            'pesan' => "Data RM {$noRmLama} berhasil digabungkan ke RM {$noRmBaru}.",
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payloadDataPasien(array $data): array
    {
        $birthDate = CarbonImmutable::parse((string) $data['tgl_lahir']);

        return [
            'nm_pasien' => $this->stringOrDefault($data['nm_pasien'] ?? null),
            'jk' => (string) $data['jk'],
            'tmp_lahir' => $this->stringOrDefault($data['tmp_lahir'] ?? null),
            'tgl_lahir' => $birthDate->toDateString(),
            'umur' => $this->umurLengkap($birthDate),
            'nm_ibu' => $this->stringOrDefault($data['nm_ibu'] ?? null),
            'alamat' => $this->stringOrDefault($data['alamat'] ?? null),
            'gol_darah' => $this->stringOrDefault($data['gol_darah'] ?? '-', '-'),
            'pekerjaan' => $this->stringOrDefault($data['pekerjaan'] ?? null, '-'),
            'stts_nikah' => $this->stringOrDefault($data['stts_nikah'] ?? null, 'BELUM MENIKAH'),
            'agama' => $this->stringOrDefault($data['agama'] ?? null, 'Islam'),
            'no_tlp' => $this->stringOrDefault($data['no_tlp'] ?? null, '-'),
            'no_ktp' => $this->stringOrDefault($data['no_ktp'] ?? null, '-'),
            'pnd' => $this->stringOrDefault($data['pnd'] ?? '-', '-'),
            'keluarga' => $this->stringOrDefault($data['keluarga'] ?? 'AYAH', 'AYAH'),
            'namakeluarga' => $this->stringOrDefault($data['namakeluarga'] ?? null, '-'),
            'kd_pj' => $this->stringOrDefault($data['kd_pj'] ?? null),
            'no_peserta' => $this->stringOrDefault($data['no_peserta'] ?? null, '-'),
            'kd_kel' => $this->integerOrDefault($data['kd_kel'] ?? null),
            'kd_kec' => $this->integerOrDefault($data['kd_kec'] ?? null),
            'kd_kab' => $this->integerOrDefault($data['kd_kab'] ?? null),
            'kd_prop' => $this->integerOrDefault($data['kd_prop'] ?? null, 1),
            'pekerjaanpj' => $this->stringOrDefault($data['pekerjaanpj'] ?? null, '-'),
            'alamatpj' => $this->stringOrDefault($data['alamatpj'] ?? null, '-'),
            'kelurahanpj' => $this->stringOrDefault($data['kelurahanpj'] ?? null, '-'),
            'kecamatanpj' => $this->stringOrDefault($data['kecamatanpj'] ?? null, '-'),
            'kabupatenpj' => $this->stringOrDefault($data['kabupatenpj'] ?? null, '-'),
            'propinsipj' => 'Aceh',
            'perusahaan_pasien' => $this->stringOrDefault($data['perusahaan_pasien'] ?? null, '-'),
            'suku_bangsa' => $this->integerOrDefault($data['suku_bangsa'] ?? null, 1),
            'bahasa_pasien' => $this->integerOrDefault($data['bahasa_pasien'] ?? null, 1),
            'cacat_fisik' => $this->integerOrDefault($data['cacat_fisik'] ?? null, 1),
            'email' => $this->stringOrDefault($data['email'] ?? null, '-'),
        ];
    }

    private function umurLengkap(CarbonImmutable $birthDate): string
    {
        $diff = $birthDate->diff(now());

        return "{$diff->y} Th {$diff->m} Bl {$diff->d} Hr";
    }

    private function stringOrDefault(mixed $value, string $default = ''): string
    {
        $string = trim((string) ($value ?? ''));

        return $string !== '' ? $string : $default;
    }

    private function integerOrDefault(mixed $value, int $default = 0): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return (int) $value;
    }

    /**
     * @param  array{kd_pj?:string|null}  $data
     */
    private function resolvedPaymentCode(array $data, object $patient): string
    {
        if (filled($data['kd_pj'] ?? null)) {
            return (string) $data['kd_pj'];
        }

        if (filled($patient->kd_pj ?? null)) {
            return (string) $patient->kd_pj;
        }

        return (string) DB::table('penjab')
            ->where('status', '1')
            ->where('png_jawab', 'UMUM')
            ->value('kd_pj');
    }

    /**
     * @param  array{p_jawab?:string|null,almt_pj?:string|null,hubunganpj?:string|null}  $data
     * @return array{name:string,address:string,relationship:string}
     */
    private function responsiblePersonData(array $data, object $patient): array
    {
        return [
            'name' => filled($data['p_jawab'] ?? null)
                ? (string) $data['p_jawab']
                : (string) ($patient->namakeluarga ?: $patient->nm_pasien),
            'address' => filled($data['almt_pj'] ?? null)
                ? (string) $data['almt_pj']
                : (string) ($patient->alamatpj ?: $patient->alamat ?: '-'),
            'relationship' => filled($data['hubunganpj'] ?? null)
                ? (string) $data['hubunganpj']
                : (string) ($patient->keluarga ?: 'DIRI SENDIRI'),
        ];
    }

    /**
     * @return array{value:int,unit:string}
     */
    private function calculateRegistrationAge(string $birthDate, CarbonImmutable $registrationDate): array
    {
        $diff = CarbonImmutable::parse($birthDate)->diff($registrationDate);

        if ($diff->y > 0) {
            return ['value' => $diff->y, 'unit' => 'Th'];
        }

        if ($diff->m > 0) {
            return ['value' => $diff->m, 'unit' => 'Bl'];
        }

        return ['value' => $diff->d, 'unit' => 'Hr'];
    }

    private function nextRegistrationNumber(string $doctorCode, CarbonImmutable $registrationDate, ?string $excludeNoRawat = null): string
    {
        $maxNumber = DB::table('reg_periksa')
            ->where('kd_dokter', $doctorCode)
            ->whereDate('tgl_registrasi', $registrationDate->toDateString())
            ->when($excludeNoRawat !== null, fn ($query) => $query->where('no_rawat', '!=', $excludeNoRawat))
            ->max('no_reg');

        return sprintf('%03d', ((int) $maxNumber) + 1);
    }

    private function nextCareNumber(CarbonImmutable $registrationDate): string
    {
        $prefix = $registrationDate->format('Y/m/d');
        $lastCareNumber = DB::table('reg_periksa')
            ->where('no_rawat', 'like', $prefix.'/%')
            ->orderByDesc('no_rawat')
            ->value('no_rawat');

        $lastSequence = $lastCareNumber ? (int) Arr::last(explode('/', (string) $lastCareNumber)) : 0;

        return $prefix.'/'.sprintf('%06d', $lastSequence + 1);
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    /**
     * @param  array{diagnosa_awal:string,kd_penyakit?:string|null,mode_diagnosa:string}  $data
     */
    private function diagnosaMasuk(array $data): string
    {
        if ($data['mode_diagnosa'] !== 'referensi' || ! filled($data['kd_penyakit'] ?? null)) {
            return trim($data['diagnosa_awal']);
        }

        $diagnosis = DB::table('penyakit')
            ->where('kd_penyakit', $data['kd_penyakit'])
            ->first(['kd_penyakit', 'nm_penyakit']);

        if ($diagnosis === null) {
            throw ValidationException::withMessages([
                'diagnosa_awal' => 'Diagnosa referensi tidak ditemukan. Pilih diagnosa dari daftar atau gunakan input manual.',
            ]);
        }

        return trim($diagnosis->kd_penyakit.' - '.$diagnosis->nm_penyakit);
    }

    /**
     * @return array{berhasil: bool, pesan: string}
     */
    private function hapusSepJikaAda(string $noRawat, string $user, string $aksi): array
    {
        $noSep = DB::table('bridging_sep')
            ->where('no_rawat', $noRawat)
            ->value('no_sep');

        if (! filled($noSep)) {
            return [
                'berhasil' => true,
                'pesan' => '',
            ];
        }

        $result = $this->vclaimService->deleteSep((string) $noSep, $user);

        if (($result['metadata']['code'] ?? null) !== '200') {
            return [
                'berhasil' => false,
                'pesan' => "Pendaftaran belum {$aksi} karena SEP gagal dihapus dari BPJS: ".$result['metadata']['message'],
            ];
        }

        DB::table('bridging_sep')->where('no_sep', $noSep)->delete();

        return [
            'berhasil' => true,
            'pesan' => '',
        ];
    }

    /**
     * @return list<string>
     */
    private function pemakaiNoRawat(string $noRawat): array
    {
        $tabelDiabaikan = [
            ...$this->tabelPendukungYangDihapus,
            'bridging_sep',
            'reg_periksa',
        ];

        $tables = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('REFERENCED_TABLE_NAME', 'reg_periksa')
            ->where('COLUMN_NAME', 'no_rawat')
            ->whereNotIn('TABLE_NAME', $tabelDiabaikan)
            ->orderBy('TABLE_NAME')
            ->pluck('TABLE_NAME')
            ->unique()
            ->values();

        $pemakai = [];

        foreach ($tables as $table) {
            if (DB::table($table)->where('no_rawat', $noRawat)->exists()) {
                $pemakai[] = (string) $table;
            }

            if (count($pemakai) >= 6) {
                break;
            }
        }

        return $pemakai;
    }

    private function paymentOptions(): array
    {
        return DB::table('penjab')
            ->where('status', '1')
            ->orderBy('png_jawab')
            ->get()
            ->map(fn (object $row): array => ['value' => (string) $row->kd_pj, 'label' => (string) $row->png_jawab])
            ->all();
    }

    /**
     * @return list<array{value:string,label:string,description:string}>
     */
    private function searchWilayah(string $table, string $kodeField, string $namaField, string $query): array
    {
        return DB::table($table)
            ->select([$kodeField, $namaField])
            ->when($query !== '', function ($builder) use ($kodeField, $namaField, $query): void {
                $builder->where(function ($subQuery) use ($kodeField, $namaField, $query): void {
                    $subQuery
                        ->where($namaField, 'like', "%{$query}%")
                        ->orWhere($kodeField, 'like', "{$query}%");
                });
            })
            ->orderBy($namaField)
            ->limit(20)
            ->get()
            ->map(fn (object $row): array => [
                'value' => (string) $row->{$kodeField},
                'label' => (string) $row->{$namaField},
                'description' => 'Kode: '.(string) $row->{$kodeField},
            ])
            ->all();
    }
}
