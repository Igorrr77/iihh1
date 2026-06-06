<?php

declare(strict_types=1);

namespace Commentor;

final class GeminiClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model
    ) {
    }

    public function generateReply(string $prompt): string
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('Не задан GEMINI_API_KEY');
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode($this->model),
            rawurlencode($this->apiKey)
        );

        $payload = [
            'contents' => [[
                'parts' => [[
                    'text' => $prompt,
                ]],
            ]],
            'generationConfig' => [
                'temperature' => 0.3,
                'topP' => 0.9,
                'maxOutputTokens' => 450,
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error !== '') {
            throw new \RuntimeException('Ошибка cURL при запросе к Gemini: ' . $error);
        }

        $json = json_decode($response, true);
        if ($httpCode >= 400) {
            $msg = $json['error']['message'] ?? 'Неизвестная ошибка API';
            throw new \RuntimeException('Gemini API вернул ошибку: ' . $msg);
        }

        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            throw new \RuntimeException('Gemini API вернул пустой ответ');
        }

        return trim($text);
    }
}
