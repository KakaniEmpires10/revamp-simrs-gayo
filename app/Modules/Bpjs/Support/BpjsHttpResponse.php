<?php

namespace App\Modules\Bpjs\Support;

use Illuminate\Http\Client\Response;
use LZCompressor\LZString;

class BpjsHttpResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function decode(Response $response, string $decryptKey): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            return [
                'metadata' => [
                    'code' => (string) $response->status(),
                    'message' => $response->body() ?: 'BPJS tidak mengembalikan JSON.',
                ],
            ];
        }

        if (isset($payload['response']) && is_string($payload['response'])) {
            $decrypted = BpjsSignature::stringDecrypt($decryptKey, $payload['response']);

            if (is_string($decrypted) && $decrypted !== '') {
                $decompressed = LZString::decompressFromEncodedURIComponent($decrypted);
                $decodedResponse = json_decode($decompressed ?: $decrypted, true);

                if (is_array($decodedResponse)) {
                    $payload['response'] = $decodedResponse;
                }
            }
        }

        return $payload;
    }
}
