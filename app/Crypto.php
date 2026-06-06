<?php

declare(strict_types=1);

namespace Commentor;

final class Crypto
{
    public static function encrypt(string $plain): string
    {
        $key = Env::get('APP_ENCRYPTION_KEY', '');
        if ($plain === '' || $key === '') {
            return $plain;
        }

        $rawKey = base64_decode($key, true);
        if (!is_string($rawKey) || strlen($rawKey) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $plain;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plain, $nonce, $rawKey);

        return 'enc:' . base64_encode($nonce . $cipher);
    }

    public static function decrypt(string $value): string
    {
        if (!str_starts_with($value, 'enc:')) {
            return $value;
        }

        $key = Env::get('APP_ENCRYPTION_KEY', '');
        if ($key === '') {
            throw new \RuntimeException('Не задан APP_ENCRYPTION_KEY для расшифровки токена');
        }

        $rawKey = base64_decode($key, true);
        $raw = base64_decode(substr($value, 4), true);
        if (!is_string($rawKey) || strlen($rawKey) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES || !is_string($raw)) {
            throw new \RuntimeException('Некорректный формат зашифрованного токена');
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $rawKey);
        if (!is_string($plain)) {
            throw new \RuntimeException('Не удалось расшифровать токен');
        }

        return $plain;
    }
}
