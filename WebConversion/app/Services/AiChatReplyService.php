<?php

declare(strict_types=1);

namespace App\Services;

final class AiChatReplyService
{
    public function generateReply(string $question, string $promptPolicy, string $replyName = 'Модератор'): ?array
    {
        $q = mb_strtolower(trim($question));
        if ($q === '') {
            return null;
        }

        if (str_contains($promptPolicy, 'ignore_price') && str_contains($q, 'цена')) {
            return null;
        }

        $text = 'Спасибо за вопрос! ';
        if (str_contains($q, 'запись')) {
            $text .= 'Да, запись будет доступна после эфира.';
        } elseif (str_contains($q, 'оплат')) {
            $text .= 'Оплата доступна кнопкой оффера внизу экрана.';
        } else {
            $text .= 'Ответили в чате, если нужно — уточните детали.';
        }

        return ['name' => $replyName, 'text' => $text];
    }
}
