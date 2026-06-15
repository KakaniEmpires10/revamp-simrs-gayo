<?php

namespace App\Modules\Bpjs\Infrastructure;

class AntrolClient
{
    public function __construct(private readonly BpjsHttpClient $client = new BpjsHttpClient('antrol')) {}

    /**
     * @param  array{kodebooking: string, taskid: string, waktu: int}  $payload
     * @return array<string, mixed>
     */
    public function updateWaktu(array $payload): array
    {
        return $this->client->post('antrean/updatewaktu', $payload);
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }
}
