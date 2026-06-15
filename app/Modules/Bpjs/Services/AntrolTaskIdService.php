<?php

namespace App\Modules\Bpjs\Services;

use App\Modules\Bpjs\Contracts\AntrolTaskIdGateway;
use App\Modules\Bpjs\Contracts\BpjsIntegrationSettings;
use App\Modules\Bpjs\Contracts\BpjsTaskIdLogRepository;
use App\Modules\Bpjs\Data\TaskIdDispatchResult;
use App\Modules\Bpjs\Infrastructure\AntrolClient;
use App\Modules\Bpjs\Jobs\SendAntrolTaskIdJob;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class AntrolTaskIdService implements AntrolTaskIdGateway
{
    public function __construct(
        private readonly AntrolClient $client,
        private readonly BpjsTaskIdLogRepository $repository,
        private readonly BpjsIntegrationSettings $settings,
    ) {}

    public function isEnabled(): bool
    {
        return $this->settings->antrolTaskIdEnabled();
    }

    public function dispatch(string $noRawat, int|string $taskId, ?CarbonInterface $waktu = null): TaskIdDispatchResult
    {
        $sentAt = CarbonImmutable::instance($waktu ?? now());

        if (! $this->isEnabled()) {
            $this->repository->recordFailure($noRawat, $taskId, 'DISABLED', 'Kirim Task ID Antrol sedang dinonaktifkan.', $sentAt);

            return TaskIdDispatchResult::skipped('Kirim Task ID Antrol sedang dinonaktifkan.', 'DISABLED');
        }

        if ($this->repository->hasSuccess($noRawat, $taskId)) {
            return TaskIdDispatchResult::skipped('Task ID Antrol sudah pernah berhasil dikirim.', 'DUPLICATE');
        }

        SendAntrolTaskIdJob::dispatch($noRawat, (string) $taskId, $sentAt->toISOString());

        return TaskIdDispatchResult::queued();
    }

    public function sendNow(string $noRawat, int|string $taskId, ?CarbonInterface $waktu = null): TaskIdDispatchResult
    {
        $sentAt = CarbonImmutable::instance($waktu ?? now());

        if (! $this->isEnabled()) {
            $this->repository->recordFailure($noRawat, $taskId, 'DISABLED', 'Kirim Task ID Antrol sedang dinonaktifkan.', $sentAt);

            return TaskIdDispatchResult::skipped('Kirim Task ID Antrol sedang dinonaktifkan.', 'DISABLED');
        }

        if (! $this->client->isConfigured()) {
            $this->repository->recordFailure($noRawat, $taskId, 'CONFIG', 'Konfigurasi Antrol BPJS belum lengkap.', $sentAt);

            return TaskIdDispatchResult::failed('Konfigurasi Antrol BPJS belum lengkap.', 'CONFIG');
        }

        if ($this->repository->hasSuccess($noRawat, $taskId)) {
            return TaskIdDispatchResult::skipped('Task ID Antrol sudah pernah berhasil dikirim.', 'DUPLICATE');
        }

        try {
            $response = $this->client->updateWaktu([
                'kodebooking' => $this->repository->bookingCodeForRawat($noRawat),
                'taskid' => (string) $taskId,
                'waktu' => $sentAt->timestamp * 1000,
            ]);
        } catch (ConnectionException $exception) {
            $this->repository->recordFailure($noRawat, $taskId, 'CONNECTION', $exception->getMessage(), $sentAt);

            return TaskIdDispatchResult::failed('Tidak bisa terhubung ke Antrol BPJS.', 'CONNECTION');
        } catch (Throwable $exception) {
            $this->repository->recordFailure($noRawat, $taskId, 'ERROR', $exception->getMessage(), $sentAt);

            return TaskIdDispatchResult::failed('Kirim Task ID Antrol gagal.', 'ERROR');
        }

        $metadata = is_array($response['metadata'] ?? null) ? $response['metadata'] : [];
        $code = (string) ($metadata['code'] ?? '');
        $message = (string) ($metadata['message'] ?? 'BPJS tidak mengembalikan metadata.');

        if ($code === '200') {
            $this->repository->recordSuccess($noRawat, $taskId, $sentAt);

            return TaskIdDispatchResult::success($message ?: 'Task ID Antrol berhasil dikirim.');
        }

        $this->repository->recordFailure($noRawat, $taskId, $code !== '' ? $code : 'UNKNOWN', $message, $sentAt);

        return TaskIdDispatchResult::failed($message, $code !== '' ? $code : 'UNKNOWN');
    }
}
