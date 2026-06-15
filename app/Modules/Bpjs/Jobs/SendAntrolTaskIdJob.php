<?php

namespace App\Modules\Bpjs\Jobs;

use App\Modules\Bpjs\Contracts\AntrolTaskIdGateway;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAntrolTaskIdJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 15;

    public function __construct(
        public readonly string $noRawat,
        public readonly string $taskId,
        public readonly ?string $waktu = null,
    ) {}

    public function handle(AntrolTaskIdGateway $gateway): void
    {
        $gateway->sendNow(
            $this->noRawat,
            $this->taskId,
            $this->waktu === null ? null : CarbonImmutable::parse($this->waktu),
        );
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }
}
