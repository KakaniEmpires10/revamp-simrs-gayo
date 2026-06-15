<?php

namespace App\Modules\Bpjs\Infrastructure;

class VClaimClient
{
    public function __construct(private readonly BpjsHttpClient $client = new BpjsHttpClient('vclaim')) {}

    /**
     * @return array<string, mixed>
     */
    public function get(string $endpoint): array
    {
        return $this->client->get($endpoint);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $payload): array
    {
        return $this->client->post($endpoint, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function put(string $endpoint, array $payload): array
    {
        return $this->client->put($endpoint, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function delete(string $endpoint, array $payload): array
    {
        return $this->client->delete($endpoint, $payload);
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }
}
