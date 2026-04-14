<?php

declare(strict_types=1);

namespace App\Telegram;

final class TelegramClient
{
    public function __construct(private readonly string $token)
    {
    }

    public function call(string $method, array $payload = []): array
    {
        $url = sprintf('https://api.telegram.org/bot%s/%s', $this->token, $method);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return ['ok' => false, 'http_code' => $code, 'error' => $error];
        }

        return [
            'ok' => $code >= 200 && $code < 300,
            'http_code' => $code,
            'body' => json_decode((string)$response, true),
        ];
    }
}
