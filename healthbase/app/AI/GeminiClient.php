<?php

declare(strict_types=1);

namespace App\AI;

use App\Services\Logger;

class GeminiClient
{
    private string $base = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(private string $apiKey, private string $modelId, private Logger $logger)
    {
    }

    public function generateJson(string $prompt): ?array
    {
        $url = sprintf('%s/%s:generateContent?key=%s', $this->base, urlencode($this->modelId), urlencode($this->apiKey));
        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['responseMimeType' => 'application/json'],
        ];

        for ($i = 1; $i <= 3; $i++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($body !== false && $code >= 200 && $code < 300) {
                $decoded = json_decode($body, true);
                $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if (!$text) {
                    continue;
                }
                $json = json_decode($text, true);
                if (is_array($json)) {
                    return $json;
                }
            }

            $this->logger->log('ai', "Gemini error {$code}: {$error}");
            usleep($i * 250000);
        }

        return null;
    }
}
