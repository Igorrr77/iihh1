<?php

declare(strict_types=1);

namespace App\Services;

final class AceContentService
{
    public function generatePack(string $transcript): array
    {
        $clean = trim($transcript);
        if ($clean === '') {
            return [];
        }

        $short = mb_substr($clean, 0, 400);

        return [
            'summary' => 'Краткая выжимка: ' . $short,
            'blog_post' => "Заголовок: Главные инсайты вебинара\n\n" . $short,
            'email_followup' => "Тема: Запись и материалы вебинара\n\n" . $short,
            'social_post' => '5 ключевых мыслей с вебинара: ' . mb_substr($short, 0, 180),
        ];
    }

    public function qualityBenchmark(array $contents): array
    {
        $scores = [];
        foreach ($contents as $item) {
            $text = (string) ($item['content_text'] ?? '');
            $len = mb_strlen($text);
            $hasCta = str_contains(mb_strtolower($text), 'запись') || str_contains(mb_strtolower($text), 'вебинара');
            $score = min(100, (int) floor($len / 10) + ($hasCta ? 20 : 0));
            $scores[] = [
                'content_type' => (string) ($item['content_type'] ?? 'unknown'),
                'length' => $len,
                'score' => $score,
            ];
        }

        return [
            'items' => $scores,
            'average_score' => $scores === [] ? 0 : (int) floor(array_sum(array_column($scores, 'score')) / count($scores)),
        ];
    }
}
