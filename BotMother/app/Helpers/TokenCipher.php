<?php

declare(strict_types=1);

namespace App\Helpers;

final class TokenCipher
{
    public static function encrypt(string $value): string
    {
        $key = hash('sha256', getenv('APP_KEY') ?: 'botmother-dev-key', true);
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    public static function decrypt(string $encrypted): string
    {
        $raw = base64_decode($encrypted, true) ?: '';
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $key = hash('sha256', getenv('APP_KEY') ?: 'botmother-dev-key', true);
        return (string)openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }
}
