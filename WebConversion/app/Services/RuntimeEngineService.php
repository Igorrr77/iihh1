<?php

declare(strict_types=1);

namespace App\Services;

final class RuntimeEngineService
{
    public function dueEvents(array $events, int $elapsedSeconds): array
    {
        $due = [];
        foreach ($events as $event) {
            $at = (int) ($event['at'] ?? -1);
            if ($at >= 0 && $at <= $elapsedSeconds) {
                $due[] = $event;
            }
        }

        return $due;
    }
}
