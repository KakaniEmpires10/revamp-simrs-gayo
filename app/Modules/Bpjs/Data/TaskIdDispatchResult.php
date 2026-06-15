<?php

namespace App\Modules\Bpjs\Data;

class TaskIdDispatchResult
{
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly ?string $code = null,
    ) {}

    public static function queued(): self
    {
        return new self('queued', 'Task ID Antrol masuk antrean kirim.');
    }

    public static function skipped(string $message, ?string $code = null): self
    {
        return new self('skipped', $message, $code);
    }

    public static function success(string $message = 'Task ID Antrol berhasil dikirim.'): self
    {
        return new self('success', $message, '200');
    }

    public static function failed(string $message, ?string $code = null): self
    {
        return new self('failed', $message, $code);
    }
}
