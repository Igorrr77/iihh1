<?php

declare(strict_types=1);

namespace App\Services;

final class ChatModerationService
{
    /** @var string[] */
    private array $blockedWords = ['spam', 'scam', 'fraud'];

    public function isAllowed(string $message): bool
    {
        $lower = mb_strtolower($message);
        foreach ($this->blockedWords as $word) {
            if (str_contains($lower, $word)) {
                return false;
            }
        }

        if ($this->tooManyLinks($message) || $this->tooManyRepeats($message) || $this->allCapsAbuse($message)) {
            return false;
        }

        return true;
    }

    private function tooManyLinks(string $message): bool
    {
        preg_match_all('/https?:\/\//i', $message, $matches);
        return count($matches[0] ?? []) > 2;
    }

    private function tooManyRepeats(string $message): bool
    {
        return preg_match('/(.)\1{7,}/u', $message) === 1;
    }

    private function allCapsAbuse(string $message): bool
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $message) ?? '';
        if (mb_strlen($letters) < 10) {
            return false;
        }

        return mb_strtoupper($letters) === $letters;
    }
}
