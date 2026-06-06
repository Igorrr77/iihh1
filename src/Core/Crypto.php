<?php

declare(strict_types=1);

namespace App\Core;

final class Crypto
{
    private string $key;

    public function __construct()
    {
        $raw = (string) Env::get('APP_KEY', '');
        $this->key = hash('sha256', $raw, true);
    }

    public function encrypt(string $plain): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $tag . $cipher);
    }

    public function decrypt(string $cipherText): string
    {
        $bin = base64_decode($cipherText, true);
        if ($bin === false || strlen($bin) < 28) {
            return '';
        }

        $iv = substr($bin, 0, 12);
        $tag = substr($bin, 12, 16);
        $cipher = substr($bin, 28);

        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? '' : $plain;
    }
}
