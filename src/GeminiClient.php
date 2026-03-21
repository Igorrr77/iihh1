<?php

declare(strict_types=1);

namespace App;

final class GeminiClient
{
    public function __construct(
        private string $apiKey,
        private string $model,
    ) {
    }

    public function analyzeSpeechChunk(string $transcriptChunk, string $productContext, string $languageHint = 'auto'): array
    {
        $prompt = <<<PROMPT
You are a real-time sales psychology engine.
Analyze ONLY the client speech chunk.
Language hint: {$languageHint}. Priorities: ru, uk, en, he.
Return strict JSON with keys:
- sentiment (negative|neutral|positive)
- emotions (array)
- confidence_level (0-100)
- objections (array)
- pain_points (array)
- lead_score (0-100)
- churn_risk (0-100)
- personality_profile (object with traits, preferred_levers, taboo_phrases)
- live_coaching (array of short suggestions for seller)
- response_patterns (array of persuasive language templates)
Input chunk:
{$transcriptChunk}
Product context:
{$productContext}
PROMPT;

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
                'responseMimeType' => 'application/json',
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 25,
        ]);

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status > 299 || !$result) {
            return $this->fallback($transcriptChunk);
        }

        $decoded = json_decode($result, true);
        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text)) {
            return $this->fallback($transcriptChunk);
        }

        $analysis = json_decode($text, true);

        return is_array($analysis) ? $analysis : $this->fallback($transcriptChunk);
    }

    private function fallback(string $chunk): array
    {
        return [
            'sentiment' => 'neutral',
            'emotions' => ['unknown'],
            'confidence_level' => 50,
            'objections' => [],
            'pain_points' => [],
            'lead_score' => 50,
            'churn_risk' => 50,
            'personality_profile' => [
                'traits' => ['insufficient_data'],
                'preferred_levers' => ['value_proof', 'social_proof'],
                'taboo_phrases' => [],
            ],
            'live_coaching' => [
                'Уточните главный критерий выбора клиента.',
            ],
            'response_patterns' => [
                'Понимаю вас. Если ключевая цель — {pain_point}, давайте сравним 2 варианта по ROI.',
            ],
            'raw_chunk' => $chunk,
        ];
    }
}
