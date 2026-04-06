<?php

declare(strict_types=1);

namespace App\Services;

use App\AI\GeminiClient;

class AIClassifierService
{
    public function __construct(private GeminiClient $gemini, private Logger $logger)
    {
    }

    public function classify(array $video, array $taxonomy, array $ruleHints): array
    {
        $prompt = $this->buildClassificationPrompt($video, $taxonomy, $ruleHints);
        $result = $this->gemini->generateJson($prompt);
        if (!$this->isValidResult($result, $taxonomy)) {
            $this->logger->log('ai', 'Classification JSON invalid');
            return ['status' => 'manual_review', 'confidence' => 0.0, 'data' => null];
        }

        $validatorPrompt = $this->buildValidationPrompt($video, $result, $taxonomy);
        $validator = $this->gemini->generateJson($validatorPrompt);
        $verdict = $validator['verdict'] ?? 'manual_review';
        $confidence = (float)($result['confidence'] ?? 0);

        return [
            'status' => ($confidence >= 0.92 && $verdict === 'auto_approve') ? 'auto_approve' : 'manual_review',
            'confidence' => $confidence,
            'validator' => $validator,
            'data' => $result,
        ];
    }

    private function buildClassificationPrompt(array $video, array $taxonomy, array $ruleHints): string
    {
        $schema = file_get_contents(root_path('app/AI/Prompts/classification_v1.txt'));
        return strtr($schema, [
            '{{title}}' => $video['title'] ?? '',
            '{{description}}' => '',
            '{{taxonomy}}' => json_encode(array_column($taxonomy, 'slug'), JSON_UNESCAPED_UNICODE),
            '{{hints}}' => json_encode($ruleHints, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function buildValidationPrompt(array $video, array $classification, array $taxonomy): string
    {
        $schema = file_get_contents(root_path('app/AI/Prompts/validation_v1.txt'));
        return strtr($schema, [
            '{{title}}' => $video['title'] ?? '',
            '{{description}}' => '',
            '{{taxonomy}}' => json_encode(array_column($taxonomy, 'slug'), JSON_UNESCAPED_UNICODE),
            '{{classification}}' => json_encode($classification, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function isValidResult(?array $result, array $taxonomy): bool
    {
        if (!$result || empty($result['primary_category_slug'])) {
            return false;
        }
        $allowed = array_column($taxonomy, 'slug');
        if (!in_array($result['primary_category_slug'], $allowed, true)) {
            return false;
        }
        $result['short_summary'] = mb_substr((string)($result['short_summary'] ?? ''), 0, 350);
        return true;
    }
}
