<?php

declare(strict_types=1);

namespace App\Services;

final class AiScenarioGenerator
{
    public function generate(string $transcript, int $virtualViewers = 200): array
    {
        $length = mb_strlen($transcript);
        $estimatedMinutes = max(5, (int) ceil($length / 1200));

        return [
            'meta' => [
                'model' => getenv('AI_MODEL') ?: 'gemini-3.1-flash-lite',
                'estimated_minutes' => $estimatedMinutes,
                'virtual_viewers' => $virtualViewers,
            ],
            'events' => [
                ['at' => 0, 'type' => 'video_start'],
                ['at' => 45, 'type' => 'host_line', 'payload' => ['name' => 'Эксперт', 'text' => 'Добро пожаловать на вебинар!']],
                ['at' => 180, 'type' => 'viewer_question', 'payload' => ['name' => 'Анна', 'text' => 'Будет ли запись?']],
                ['at' => 195, 'type' => 'ai_answer', 'payload' => ['name' => 'Модератор', 'text' => 'Да, запись и бонусы будут доступны после эфира.']],
                ['at' => max(300, $estimatedMinutes * 60 - 600), 'type' => 'offer_button', 'payload' => ['ttl_sec' => 900]],
            ],
        ];
    }
}
