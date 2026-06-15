<?php

namespace App\Modules\Bpjs\Infrastructure;

use App\Modules\Bpjs\Contracts\BpjsTaskIdLogRepository;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DatabaseTaskIdLogRepository implements BpjsTaskIdLogRepository
{
    public function bookingCodeForRawat(string $noRawat): string
    {
        $bookingCode = DB::table('referensi_mobilejkn_bpjs')
            ->where('no_rawat', $noRawat)
            ->value('nobooking');

        return is_string($bookingCode) && $bookingCode !== '' ? $bookingCode : $noRawat;
    }

    public function hasSuccess(string $noRawat, int|string $taskId): bool
    {
        return DB::table('referensi_mobilejkn_bpjs_taskid')
            ->where('no_rawat', $noRawat)
            ->where('taskid', (string) $taskId)
            ->exists();
    }

    public function recordSuccess(string $noRawat, int|string $taskId, CarbonInterface $waktu): void
    {
        DB::table('referensi_mobilejkn_bpjs_taskid')->updateOrInsert(
            [
                'no_rawat' => $noRawat,
                'taskid' => (string) $taskId,
            ],
            [
                'waktu' => $waktu->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function recordFailure(string $noRawat, int|string $taskId, string $code, string $message, CarbonInterface $waktu): void
    {
        DB::table('uxui_taskid_errors')->insert([
            'no_rawat' => $noRawat,
            'taskid' => (int) $taskId,
            'kode' => $code,
            'message' => $message,
            'waktu' => $waktu->format('Y-m-d H:i:s'),
        ]);
    }
}
