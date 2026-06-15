<?php

namespace App\Modules\Bpjs\Infrastructure;

use App\Modules\Bpjs\Support\BpjsHttpResponse;
use App\Modules\Bpjs\Support\BpjsSignature;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BpjsHttpClient
{
    public function __construct(private readonly string $serviceName) {}

    /**
     * @return array<string, mixed>
     */
    public function get(string $endpoint): array
    {
        [$request, $decryptKey, $headers] = $this->request();
        $url = $this->url($endpoint);
        $response = $request->get($url);
        $this->logResponse('GET', $url, $response, $headers);

        return BpjsHttpResponse::decode($response, $decryptKey);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $payload): array
    {
        [$request, $decryptKey, $headers] = $this->request();
        $url = $this->url($endpoint);
        $response = $request->post($url, $payload);
        $this->logResponse('POST', $url, $response, $headers);

        return BpjsHttpResponse::decode($response, $decryptKey);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function put(string $endpoint, array $payload): array
    {
        [$request, $decryptKey, $headers] = $this->request();
        $url = $this->url($endpoint);
        $response = $request->put($url, $payload);
        $this->logResponse('PUT', $url, $response, $headers);

        return BpjsHttpResponse::decode($response, $decryptKey);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function delete(string $endpoint, array $payload = []): array
    {
        [$request, $decryptKey, $headers] = $this->request();
        $url = $this->url($endpoint);
        $response = $request->send('DELETE', $url, ['json' => $payload]);
        $this->logResponse('DELETE', $url, $response, $headers);

        return BpjsHttpResponse::decode($response, $decryptKey);
    }

    public function isConfigured(): bool
    {
        return filled($this->config('base_url'))
            && filled($this->config('service'))
            && filled($this->config('cons_id'))
            && filled($this->config('secret_key'))
            && filled($this->config('user_key'));
    }

    /**
     * @return array{0: PendingRequest, 1: string, 2: array<string, string>}
     */
    private function request(): array
    {
        $timestamp = BpjsSignature::timestamp();
        $consId = (string) $this->config('cons_id');
        $secretKey = (string) $this->config('secret_key');
        $headers = [
            'X-cons-id' => $consId,
            'X-timestamp' => $timestamp,
            'X-signature' => BpjsSignature::signature($consId, $secretKey, $timestamp),
            'user_key' => (string) $this->config('user_key'),
            'Content-Type' => 'Application/json',
        ];

        $request = Http::acceptJson()
            ->timeout((int) $this->config('timeout'))
            ->connectTimeout((int) $this->config('connect_timeout'))
            ->retry((int) $this->config('retry_times'), (int) $this->config('retry_sleep'), throw: false)
            ->when(! $this->shouldVerifySsl(), fn (PendingRequest $request): PendingRequest => $request->withoutVerifying())
            ->withHeaders($headers);

        return [$request, BpjsSignature::decryptKey($consId, $secretKey, $timestamp), $headers];
    }

    private function url(string $endpoint): string
    {
        $baseUrl = rtrim((string) $this->config('base_url'), '/');
        $service = trim((string) $this->config('service'), '/');

        if (str($baseUrl)->endsWith('/'.$service)) {
            return $baseUrl.'/'.ltrim($endpoint, '/');
        }

        return $baseUrl.'/'.$service.'/'.ltrim($endpoint, '/');
    }

    private function config(string $key): mixed
    {
        return Arr::get(config("bpjs.{$this->serviceName}"), $key);
    }

    private function shouldVerifySsl(): bool
    {
        return (bool) ($this->config('verify_ssl') ?? true);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function logResponse(string $method, string $url, Response $response, array $headers): void
    {
        if (app()->isProduction()) {
            return;
        }

        $payload = $response->json();

        if (is_array($payload)) {
            return;
        }

        Log::warning('BPJS HTTP response is not JSON.', [
            'service' => $this->serviceName,
            'method' => $method,
            'url' => $url,
            'status' => $response->status(),
            'json' => is_array($payload),
            'headers' => $this->maskedHeaders($headers),
            'body' => Str::limit($response->body(), 500),
        ]);
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function maskedHeaders(array $headers): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => $headers['Content-Type'] ?? '',
            'X-cons-id' => $this->mask($headers['X-cons-id'] ?? ''),
            'X-timestamp' => $headers['X-timestamp'] ?? '',
            'X-signature' => $this->mask($headers['X-signature'] ?? ''),
            'user_key' => $this->mask($headers['user_key'] ?? ''),
        ];
    }

    private function mask(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (strlen($value) <= 8) {
            return str_repeat('*', strlen($value));
        }

        return Str::mask($value, '*', 4, strlen($value) - 8);
    }
}
