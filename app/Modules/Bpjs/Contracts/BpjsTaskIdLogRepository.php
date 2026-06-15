<?php

namespace App\Modules\Bpjs\Contracts;

use Carbon\CarbonInterface;

interface BpjsTaskIdLogRepository
{
    public function bookingCodeForRawat(string $noRawat): string;

    public function hasSuccess(string $noRawat, int|string $taskId): bool;

    public function recordSuccess(string $noRawat, int|string $taskId, CarbonInterface $waktu): void;

    public function recordFailure(string $noRawat, int|string $taskId, string $code, string $message, CarbonInterface $waktu): void;
}
