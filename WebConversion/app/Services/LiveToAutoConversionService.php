<?php

declare(strict_types=1);

namespace App\Services;

final class LiveToAutoConversionService
{
    public function convert(array $events): array
    {
        // baseline conversion: keep ordering and normalize missing timestamps
        usort($events, static fn (array $a, array $b): int => ((int) ($a['second_from_start'] ?? 0)) <=> ((int) ($b['second_from_start'] ?? 0)));

        $normalized = [];
        foreach ($events as $event) {
            $normalized[] = [
                'second_from_start' => max(0, (int) ($event['second_from_start'] ?? 0)),
                'event_type' => (string) ($event['event_type'] ?? 'chat'),
                'payload' => $event['payload'] ?? null,
            ];
        }

        return $normalized;
    }
}
