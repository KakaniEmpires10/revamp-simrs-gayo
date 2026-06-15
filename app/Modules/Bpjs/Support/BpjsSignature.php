<?php

namespace App\Modules\Bpjs\Support;

class BpjsSignature
{
    public static function timestamp(): string
    {
        return (string) (time() - strtotime('1970-01-01 00:00:00'));
    }

    public static function signature(string $consId, string $secretKey, string $timestamp): string
    {
        return base64_encode(hash_hmac('sha256', $consId.'&'.$timestamp, $secretKey, true));
    }

    public static function decryptKey(string $consId, string $secretKey, string $timestamp): string
    {
        return $consId.$secretKey.$timestamp;
    }

    public static function stringDecrypt(string $key, string $payload): string|false
    {
        $keyHash = hex2bin(hash('sha256', $key));
        $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);

        return openssl_decrypt(base64_decode($payload), 'AES-256-CBC', $keyHash, OPENSSL_RAW_DATA, $iv);
    }
}
