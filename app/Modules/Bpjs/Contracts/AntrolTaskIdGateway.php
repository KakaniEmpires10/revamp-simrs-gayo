<?php

namespace App\Modules\Bpjs\Contracts;

use App\Modules\Bpjs\Data\TaskIdDispatchResult;
use Carbon\CarbonInterface;

interface AntrolTaskIdGateway
{
    public function isEnabled(): bool;

    public function dispatch(string $noRawat, int|string $taskId, ?CarbonInterface $waktu = null): TaskIdDispatchResult;

    public function sendNow(string $noRawat, int|string $taskId, ?CarbonInterface $waktu = null): TaskIdDispatchResult;
}
