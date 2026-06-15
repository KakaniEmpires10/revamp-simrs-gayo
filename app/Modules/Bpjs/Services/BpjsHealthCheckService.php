<?php

namespace App\Modules\Bpjs\Services;

use App\Modules\Bpjs\Infrastructure\BpjsHttpClient;
use Illuminate\Support\Facades\Http;
use Throwable;

class BpjsHealthCheckService
{
    /**
     * @return array{summary: array{total: int, online: int, offline: int, average_duration_ms: int|null}, services: array<int, array<string, mixed>>}
     */
    public function checkAll(): array
    {
        $services = collect([
            ...$this->signedChecks(),
            ...$this->httpChecks(),
        ])->map(fn (array $check): array => $this->runCheck($check))->values()->all();

        $online = collect($services)->where('status', 'online')->count();
        $durations = collect($services)->pluck('duration_ms')->filter(fn (?int $duration): bool => $duration !== null);

        return [
            'summary' => [
                'total' => count($services),
                'online' => $online,
                'offline' => count($services) - $online,
                'average_duration_ms' => $durations->isNotEmpty() ? (int) round($durations->average()) : null,
            ],
            'services' => $services,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function signedChecks(): array
    {
        return [
            [
                'id' => 'finger_vclaim',
                'title' => 'Fingerprint VClaim',
                'description' => 'Validasi endpoint fingerprint peserta VClaim.',
                'category' => 'VClaim',
                'service' => 'vclaim',
                'method' => 'GET',
                'endpoint' => 'SEP/FingerPrint/Peserta/0000005140945/TglPelayanan/2025-01-23',
            ],
            [
                'id' => 'cek_sep',
                'title' => 'Cek SEP',
                'description' => 'Validasi endpoint pencarian SEP.',
                'category' => 'VClaim',
                'service' => 'vclaim',
                'method' => 'GET',
                'endpoint' => 'SEP/34343434',
            ],
            [
                'id' => 'buat_sep',
                'title' => 'Pembuatan SEP',
                'description' => 'Validasi endpoint pembuatan SEP dengan payload kosong seperti legacy check.',
                'category' => 'VClaim',
                'service' => 'vclaim',
                'method' => 'POST',
                'endpoint' => 'SEP/2.0/insert',
            ],
            [
                'id' => 'surat_kontrol',
                'title' => 'Surat Kontrol',
                'description' => 'Validasi endpoint pencarian surat kontrol.',
                'category' => 'VClaim',
                'service' => 'vclaim',
                'method' => 'GET',
                'endpoint' => 'RencanaKontrol/noSuratKontrol/3233423',
            ],
            [
                'id' => 'buat_surat_kontrol',
                'title' => 'Buat Surat Kontrol',
                'description' => 'Validasi endpoint pembuatan rencana kontrol.',
                'category' => 'VClaim',
                'service' => 'vclaim',
                'method' => 'GET',
                'endpoint' => 'RencanaKontrol/RencanaKontrol/insert',
            ],
            [
                'id' => 'buat_spri',
                'title' => 'Buat SPRI',
                'description' => 'Validasi endpoint pembuatan SPRI.',
                'category' => 'VClaim',
                'service' => 'vclaim',
                'method' => 'GET',
                'endpoint' => 'RencanaKontrol/RencanaKontrol/InsertSPRI',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function httpChecks(): array
    {
        $vclaimBaseUrl = rtrim((string) config('bpjs.vclaim.base_url'), '/');
        $vclaimService = trim((string) config('bpjs.vclaim.service', 'vclaim-rest'), '/');
        $antrolBaseUrl = rtrim((string) config('bpjs.antrol.base_url'), '/');
        $antrolService = trim((string) config('bpjs.antrol.service', 'antreanrs'), '/');

        return [
            [
                'id' => 'cek_koneksi_fingerprint',
                'title' => 'Koneksi Fingerprint',
                'description' => 'Cek akses service fingerprint BPJS.',
                'category' => 'HTTP',
                'url' => config('bpjs.health_checks.fingerprint_url'),
            ],
            [
                'id' => 'cek_koneksi_frista',
                'title' => 'Koneksi Frista',
                'description' => 'Cek akses service Frista BPJS.',
                'category' => 'HTTP',
                'url' => config('bpjs.health_checks.frista_url'),
            ],
            [
                'id' => 'cek_koneksi_icare',
                'title' => 'Koneksi i-Care',
                'description' => 'Cek akses service i-Care BPJS.',
                'category' => 'HTTP',
                'url' => config('bpjs.health_checks.icare_url'),
            ],
            [
                'id' => 'cek_koneksi_aplicare',
                'title' => 'Koneksi Aplicare',
                'description' => 'Cek akses service Aplicare BPJS.',
                'category' => 'HTTP',
                'url' => config('bpjs.health_checks.aplicare_url'),
            ],
            [
                'id' => 'cek_koneksi_antreanrs',
                'title' => 'Koneksi Antrean RS',
                'description' => 'Cek akses service Antrean RS dan jadwal dokter.',
                'category' => 'HTTP',
                'url' => config('bpjs.health_checks.antrean_rs_url') ?: $this->joinUrl($antrolBaseUrl, $antrolService, 'antrean/batal'),
            ],
            [
                'id' => 'cek_koneksi_vclaim',
                'title' => 'Koneksi VClaim',
                'description' => 'Cek akses base service VClaim.',
                'category' => 'HTTP',
                'url' => config('bpjs.health_checks.vclaim_url') ?: $this->joinUrl($vclaimBaseUrl, $vclaimService),
            ],
            [
                'id' => 'cek_koneksi_lupis',
                'title' => 'Koneksi Lupis',
                'description' => 'Cek akses service Lupis BPJS.',
                'category' => 'HTTP',
                'url' => config('bpjs.health_checks.lupis_url'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $check
     * @return array<string, mixed>
     */
    private function runCheck(array $check): array
    {
        $startedAt = microtime(true);

        try {
            $response = isset($check['url'])
                ? $this->runHttpCheck((string) $check['url'])
                : $this->runSignedCheck($check);

            $duration = $this->duration($startedAt);
            $online = $response['online'];

            return [
                ...$this->baseResult($check, $duration),
                'status' => $online ? 'online' : 'offline',
                'metadata' => [
                    'code' => $response['code'],
                    'message' => $response['message'],
                ],
            ];
        } catch (Throwable $exception) {
            report($exception);
            $duration = $this->duration($startedAt);

            return [
                ...$this->baseResult($check, $duration),
                'status' => 'offline',
                'metadata' => [
                    'code' => 'CONNECTION_ERROR',
                    'message' => 'Gagal terhubung ke service BPJS.',
                ],
            ];
        }
    }

    /**
     * @return array{online: bool, code: string, message: string}
     */
    private function runHttpCheck(string $url): array
    {
        if ($url === '') {
            return [
                'online' => false,
                'code' => 'CONFIG',
                'message' => 'URL service belum dikonfigurasi.',
            ];
        }

        $response = Http::timeout(10)
            ->connectTimeout(5)
            ->withoutVerifying()
            ->withOptions(['http_errors' => false])
            ->head($url);
        $status = $response->status();
        $online = ($status >= 200 && $status < 400) || $status === 405;

        return [
            'online' => $online,
            'code' => (string) $status,
            'message' => $online ? 'Online' : 'Offline',
        ];
    }

    /**
     * @param  array<string, mixed>  $check
     * @return array{online: bool, code: string, message: string}
     */
    private function runSignedCheck(array $check): array
    {
        $client = new BpjsHttpClient((string) $check['service']);

        if (! $client->isConfigured()) {
            return [
                'online' => false,
                'code' => 'CONFIG',
                'message' => 'Konfigurasi service belum lengkap.',
            ];
        }

        $payload = $check['method'] === 'POST'
            ? $client->post((string) $check['endpoint'], [])
            : $client->get((string) $check['endpoint']);

        $metadata = $payload['metadata'] ?? $payload['metaData'] ?? [];

        return [
            'online' => true,
            'code' => (string) ($metadata['code'] ?? 'CONNECTED'),
            'message' => (string) ($metadata['message'] ?? 'Service merespons.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $check
     * @return array<string, mixed>
     */
    private function baseResult(array $check, int $duration): array
    {
        return [
            'id' => $check['id'],
            'title' => $check['title'],
            'description' => $check['description'],
            'category' => $check['category'],
            'method' => $check['method'] ?? 'HEAD',
            'target' => $check['endpoint'] ?? $check['url'] ?? '',
            'duration_ms' => $duration,
            'speed' => $this->connectionSpeed($duration),
        ];
    }

    private function duration(float $startedAt): int
    {
        return max(1, (int) round((microtime(true) - $startedAt) * 1000));
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

    private function joinUrl(string ...$segments): string
    {
        $filtered = array_values(array_filter($segments, fn (string $segment): bool => $segment !== ''));

        if ($filtered === []) {
            return '';
        }

        return array_shift($filtered).($filtered === [] ? '' : '/'.implode('/', array_map(fn (string $segment): string => trim($segment, '/'), $filtered)));
    }
}
