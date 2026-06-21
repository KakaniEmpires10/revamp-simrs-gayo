<?php

namespace App\Modules\Bpjs\Services;

use App\Modules\Bpjs\Infrastructure\VClaimClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class VClaimService
{
    public function __construct(private readonly VClaimClient $client) {}

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, duration_ms: int|null, speed: array{label: string, variant: string}|null}
     */
    public function checkConnection(): array
    {
        if (! $this->client->isConfigured()) {
            return [
                ...$this->failure('CONFIG', 'Konfigurasi VClaim belum lengkap.'),
                'duration_ms' => null,
                'speed' => null,
            ];
        }

        $startedAt = microtime(true);
        $result = $this->request(fn (): array => $this->client->get('referensi/diagnosa/A00'));
        $duration = max(1, (int) round((microtime(true) - $startedAt) * 1000));

        return [
            ...$result,
            'duration_ms' => $duration,
            'speed' => $this->connectionSpeed($duration),
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function monitoringVisits(string $date, string $serviceType): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("Monitoring/Kunjungan/Tanggal/{$date}/JnsPelayanan/{$serviceType}"),
        );

        return [
            ...$result,
            'rows' => $this->rows($result, 'sep'),
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function referralsForParticipant(string $cardNumber, string $source): array
    {
        $prefix = $source === 'rs' ? 'Rujukan/RS/List/Peserta' : 'Rujukan/List/Peserta';
        $result = $this->request(fn (): array => $this->client->get("{$prefix}/{$cardNumber}"));

        return [
            ...$result,
            'rows' => $this->rows($result, 'rujukan'),
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, peserta: array<string, mixed>}
     */
    public function participant(string $identifierType, string $identifier, string $date): array
    {
        $segment = $identifierType === 'nik' ? 'nik' : 'nokartu';
        $result = $this->request(fn (): array => $this->client->get("Peserta/{$segment}/{$identifier}/tglSEP/{$date}"));

        $participant = Arr::get($result, 'response.peserta', []);

        return [
            ...$result,
            'peserta' => is_array($participant) ? $participant : [],
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>, peserta: array<string, mixed>}
     */
    public function referralsForIdentifier(string $identifierType, string $identifier, string $source, string $date): array
    {
        $participant = $this->participant($identifierType, $identifier, $date);

        if ($participant['metadata']['code'] !== '200') {
            return [
                'metadata' => $participant['metadata'],
                'response' => $participant['response'],
                'rows' => [],
                'peserta' => $participant['peserta'],
            ];
        }

        $cardNumber = (string) Arr::get($participant, 'peserta.noKartu', '');

        if ($cardNumber === '') {
            return [
                'metadata' => [
                    'code' => 'NO_CARD',
                    'message' => 'Nomor kartu peserta tidak ditemukan dari response BPJS.',
                ],
                'response' => $participant['response'],
                'rows' => [],
                'peserta' => $participant['peserta'],
            ];
        }

        return [
            ...$this->referralsForParticipant($cardNumber, $source),
            'peserta' => $participant['peserta'],
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function outboundReferrals(string $startDate, string $endDate): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("Rujukan/Keluar/List/tglMulai/{$startDate}/tglAkhir/{$endDate}"),
        );

        return [
            ...$result,
            'rows' => $this->rows($result, 'list'),
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rujukan: array<string, mixed>}
     */
    public function outboundReferral(string $referralNumber): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("Rujukan/Keluar/{$referralNumber}"),
        );

        $referral = Arr::get($result, 'response.rujukan', $result['response']);

        return [
            ...$result,
            'rujukan' => is_array($referral) ? $referral : [],
        ];
    }

    /**
     * @param  array{tanggal_rujukan: string, tanggal_rencana_kunjungan: string, ppk_dirujuk: string, jenis_pelayanan: string, catatan?: string|null, diagnosa_rujukan: string, tipe_rujukan: string, poli_rujukan: string}  $data
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rujukan: array<string, mixed>}
     */
    public function createOutboundReferral(array $data, string $user): array
    {
        $result = $this->request(fn (): array => $this->client->put('Rujukan/2.0/insert', [
            'request' => [
                't_rujukan' => [
                    'noSep' => $data['no_sep'],
                    'tglRujukan' => $data['tanggal_rujukan'],
                    'tglRencanaKunjungan' => $data['tanggal_rencana_kunjungan'],
                    'ppkDirujuk' => $data['ppk_dirujuk'],
                    'jnsPelayanan' => $data['jenis_pelayanan'],
                    'catatan' => $data['catatan'] ?? '',
                    'diagRujukan' => $data['diagnosa_rujukan'],
                    'tipeRujukan' => $data['tipe_rujukan'],
                    'poliRujukan' => $data['poli_rujukan'],
                    'user' => $user,
                ],
            ],
        ]));

        return [
            ...$result,
            'rujukan' => $result['response']['rujukan'],
        ];
    }

    /**
     * @param  array{tanggal_rujukan: string, tanggal_rencana_kunjungan: string, ppk_dirujuk: string, jenis_pelayanan: string, catatan?: string|null, diagnosa_rujukan: string, tipe_rujukan: string, poli_rujukan: string}  $data
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rujukan: array<string, mixed>}
     */
    public function updateOutboundReferral(string $referralNumber, array $data, string $user): array
    {
        $result = $this->request(fn (): array => $this->client->put('Rujukan/2.0/Update', [
            'request' => [
                't_rujukan' => [
                    'noRujukan' => $referralNumber,
                    'tglRujukan' => $data['tanggal_rujukan'],
                    'tglRencanaKunjungan' => $data['tanggal_rencana_kunjungan'],
                    'ppkDirujuk' => $data['ppk_dirujuk'],
                    'jnsPelayanan' => $data['jenis_pelayanan'],
                    'catatan' => $data['catatan'] ?? '',
                    'diagRujukan' => $data['diagnosa_rujukan'],
                    'tipeRujukan' => $data['tipe_rujukan'],
                    'poliRujukan' => $data['poli_rujukan'],
                    'user' => $user,
                ],
            ],
        ]));

        return [
            ...$result,
            'rujukan' => $result['response'],
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>}
     */
    public function deleteOutboundReferral(string $referralNumber, string $user): array
    {
        return $this->request(fn (): array => $this->client->delete('Rujukan/delete', [
            'request' => [
                't_rujukan' => [
                    'noRujukan' => $referralNumber,
                    'user' => $user,
                ],
            ],
        ]));
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function serviceHistory(string $cardNumber, string $startDate, string $endDate): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("monitoring/HistoriPelayanan/NoKartu/{$cardNumber}/tglMulai/{$startDate}/tglAkhir/{$endDate}"),
        );

        return [
            ...$result,
            'rows' => $this->rows($result, 'histori'),
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function prbData(string $startDate, string $endDate): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("prb/tglMulai/{$startDate}/tglAkhir/{$endDate}"),
        );

        return [
            ...$result,
            'rows' => $this->rows($result, 'prb.list'),
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function sepApprovals(string $month, string $year): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("SEP/persetujuanSEP/list/bulan/{$month}/tahun/{$year}"),
        );

        return [
            ...$result,
            'rows' => $this->rows($result, 'list'),
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function fingerprints(string $date): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("SEP/FingerPrint/List/Peserta/TglPelayanan/{$date}"),
        );

        $rows = $this->rows($result, 'list');

        return [
            ...$result,
            'rows' => $this->fingerprintRowsWithPatients($rows),
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function claimMonitoring(string $date, string $serviceType, string $status): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("Monitoring/Klaim/Tanggal/{$date}/JnsPelayanan/{$serviceType}/Status/{$status}"),
        );

        return [
            ...$result,
            'rows' => $this->rows($result, 'klaim'),
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function controlPlans(string $startDate, string $endDate, string $filter, string $type): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("RencanaKontrol/ListRencanaKontrol/tglAwal/{$startDate}/tglAkhir/{$endDate}/filter/{$filter}"),
        );

        return [
            ...$result,
            'rows' => $this->filterControlPlanRows($this->rows($result, 'list'), $type),
        ];
    }

    /**
     * @return array{
     *     metadata: array{code: string, message: string},
     *     response: array<string, mixed>,
     *     sep: array<string, mixed>
     * }
     */
    public function controlPlanBySep(string $sepNumber): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("RencanaKontrol/nosep/{$sepNumber}"),
        );

        return [
            ...$result,
            'sep' => $result['response'] ?? [],
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, kontrol: array<string, mixed>}
     */
    public function controlPlan(string $letterNumber): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("RencanaKontrol/noSuratKontrol/{$letterNumber}"),
        );

        return [
            ...$result,
            'kontrol' => $result['response'] ?? [],
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function controlPlanSpecialists(string $sepNumber, string $controlDate, string $controlType = '2'): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("RencanaKontrol/ListSpesialistik/JnsKontrol/{$controlType}/nomor/{$sepNumber}/TglRencanaKontrol/{$controlDate}"),
        );

        return [
            ...$result,
            'rows' => $this->rows($result, 'list'),
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function controlPlanDoctors(string $clinicCode, string $controlDate, string $controlType = '2'): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("RencanaKontrol/JadwalPraktekDokter/JnsKontrol/{$controlType}/KdPoli/{$clinicCode}/TglRencanaKontrol/{$controlDate}"),
        );

        return [
            ...$result,
            'rows' => $this->rows($result, 'list'),
        ];
    }

    /**
     * @param  array{no_sep: string, kode_dokter: string, poli_kontrol: string, tanggal_kontrol: string, nama_dokter?: string, nama_poli?: string}  $data
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, skdp: array<string, mixed>}
     */
    public function createControlPlan(array $data, string $user): array
    {
        $result = $this->request(fn (): array => $this->client->post('RencanaKontrol/insert', [
            'request' => [
                'noSEP' => $data['no_sep'],
                'kodeDokter' => $data['kode_dokter'],
                'poliKontrol' => $data['poli_kontrol'],
                'tglRencanaKontrol' => $data['tanggal_kontrol'],
                'user' => $user,
            ],
        ]));

        return [
            ...$result,
            'skdp' => $result['response'],
        ];
    }

    /**
     * @param  array{no_kartu: string, kode_dokter: string, poli_kontrol: string, tanggal_kontrol: string}  $data
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, spri: array<string, mixed>}
     */
    public function createInpatientPlan(array $data, string $user): array
    {
        $result = $this->request(fn (): array => $this->client->post('RencanaKontrol/InsertSPRI', [
            'request' => [
                'noKartu' => $data['no_kartu'],
                'kodeDokter' => $data['kode_dokter'],
                'poliKontrol' => $data['poli_kontrol'],
                'tglRencanaKontrol' => $data['tanggal_kontrol'],
                'user' => $user,
            ],
        ]));

        return [
            ...$result,
            'spri' => $result['response'],
        ];
    }

    /**
     * @param  array{no_sep: string, kode_dokter: string, poli_kontrol: string, tanggal_kontrol: string, nama_dokter?: string, nama_poli?: string}  $data
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, skdp: array<string, mixed>}
     */
    public function updateControlPlan(string $letterNumber, array $data, string $user): array
    {
        $result = $this->request(fn (): array => $this->client->put('RencanaKontrol/Update', [
            'request' => [
                'noSuratKontrol' => $letterNumber,
                'noSEP' => $data['no_sep'],
                'kodeDokter' => $data['kode_dokter'],
                'poliKontrol' => $data['poli_kontrol'],
                'tglRencanaKontrol' => $data['tanggal_kontrol'],
                'user' => $user,
            ],
        ]));

        return [
            ...$result,
            'skdp' => $result['response'],
        ];
    }

    /**
     * @param  array{kode_dokter: string, poli_kontrol: string, tanggal_kontrol: string}  $data
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, spri: array<string, mixed>}
     */
    public function updateInpatientPlan(string $letterNumber, array $data, string $user): array
    {
        $result = $this->request(fn (): array => $this->client->put('RencanaKontrol/UpdateSPRI', [
            'request' => [
                'noSPRI' => $letterNumber,
                'kodeDokter' => $data['kode_dokter'],
                'poliKontrol' => $data['poli_kontrol'],
                'tglRencanaKontrol' => $data['tanggal_kontrol'],
                'user' => $user,
            ],
        ]));

        return [
            ...$result,
            'spri' => $result['response'],
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>}
     */
    public function deleteControlPlan(string $letterNumber, string $user): array
    {
        return $this->request(fn (): array => $this->client->delete('RencanaKontrol/Delete', [
            'request' => [
                't_suratkontrol' => [
                    'noSuratKontrol' => $letterNumber,
                    'user' => $user,
                ],
            ],
        ]));
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, sep: array<string, mixed>}
     */
    public function sep(string $sepNumber): array
    {
        $result = $this->request(fn (): array => $this->client->get("SEP/{$sepNumber}"));

        $sep = Arr::get($result, 'response.sep', $result['response']);

        return [
            ...$result,
            'sep' => is_array($sep) ? $sep : [],
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>}
     */
    public function deleteSep(string $sepNumber, string $user): array
    {
        return $this->request(fn (): array => $this->client->delete('SEP/2.0/delete', [
            'request' => [
                't_sep' => [
                    'noSep' => $sepNumber,
                    'user' => $user,
                ],
            ],
        ]));
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function providersReferences(?string $query, string $type): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("referensi/faskes/{$query}/{$type}"),
        );

        return [
            ...$result,
            'rows' => $this->rows($result, 'faskes'),
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function specialistsReferences(?string $query): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("referensi/poli/{$query}"),
        );

        return [
            ...$result,
            'rows' => $this->rows($result, 'poli'),
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function diagnosisReferences(?string $query): array
    {
        $result = $this->request(
            fn (): array => $this->client->get("referensi/diagnosa/{$query}"),
        );

        return [
            ...$result,
            'rows' => $this->rows($result, 'diagnosa'),
        ];
    }

    /**
     * @param  callable(): array<string, mixed>  $callback
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>}
     */
    private function request(callable $callback): array
    {
        try {
            return $this->normalize($callback());
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('CONNECTION_ERROR', 'Gagal terhubung ke layanan VClaim BPJS.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>}
     */
    private function normalize(array $payload): array
    {
        $metadata = Arr::get($payload, 'metadata', Arr::get($payload, 'metaData', []));
        $response = Arr::get($payload, 'response', []);

        return [
            'metadata' => [
                'code' => (string) Arr::get($metadata, 'code', 'UNKNOWN'),
                'message' => (string) Arr::get($metadata, 'message', 'BPJS tidak mengembalikan metadata.'),
            ],
            'response' => is_array($response) ? $response : [],
        ];
    }

    /**
     * @param  array{response: array<string, mixed>}  $result
     * @return array<int, array<string, mixed>>
     */
    private function rows(array $result, string $key): array
    {
        $rows = Arr::get($result, "response.{$key}", []);

        return is_array($rows) ? array_values($rows) : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function filterControlPlanRows(array $rows, string $type): array
    {
        $expectedType = $type === 'spri' ? '1' : '2';

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => (string) ($row['jnsKontrol'] ?? '') === $expectedType,
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function fingerprintRowsWithPatients(array $rows): array
    {
        $cardNumbers = collect($rows)
            ->pluck('noKartu')
            ->filter(fn (mixed $cardNumber): bool => filled($cardNumber))
            ->map(fn (mixed $cardNumber): string => (string) $cardNumber)
            ->unique()
            ->values();

        if ($cardNumbers->isEmpty()) {
            return array_map(
                fn (array $row): array => [
                    ...$row,
                    'pasien' => $this->emptyPatient(),
                ],
                $rows,
            );
        }

        $patients = DB::table('pasien')
            ->select('no_peserta', 'no_rkm_medis', 'nm_pasien', 'jk')
            ->whereIn('no_peserta', $cardNumbers->all())
            ->get()
            ->keyBy(fn (object $patient): string => (string) $patient->no_peserta);

        return array_map(
            function (array $row) use ($patients): array {
                $patient = $patients->get((string) ($row['noKartu'] ?? ''));

                return [
                    ...$row,
                    'pasien' => $patient === null
                        ? $this->emptyPatient()
                        : [
                            'no_rkm_medis' => $patient->no_rkm_medis,
                            'nm_pasien' => $patient->nm_pasien,
                            'jk' => $patient->jk,
                        ],
                ];
            },
            $rows,
        );
    }

    /**
     * @return array{no_rkm_medis: null, nm_pasien: null, jk: null}
     */
    private function emptyPatient(): array
    {
        return [
            'no_rkm_medis' => null,
            'nm_pasien' => null,
            'jk' => null,
        ];
    }

    /**
     * @return array{metadata: array{code: string, message: string}, response: array<string, mixed>}
     */
    private function failure(string $code, string $message): array
    {
        return [
            'metadata' => [
                'code' => $code,
                'message' => $message,
            ],
            'response' => [],
        ];
    }

    /**
     * @return array{label: string, variant: string}
     */
    private function connectionSpeed(int $duration): array
    {
        if ($duration < 1000) {
            return [
                'label' => 'Cepat',
                'variant' => 'success',
            ];
        }

        if ($duration < 3000) {
            return [
                'label' => 'Sedang',
                'variant' => 'warning',
            ];
        }

        return [
            'label' => 'Lambat',
            'variant' => 'destructive',
        ];
    }
}
