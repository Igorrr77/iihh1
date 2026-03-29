<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class TimelineRepository
{
    public function addEvent(int $webinarId, int $second, string $type, array $payload): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO webinar_events (webinar_id, second_from_start, event_type, payload_json) VALUES (:webinar_id, :second_from_start, :event_type, :payload_json)'
        );
        $stmt->execute([
            'webinar_id' => $webinarId,
            'second_from_start' => $second,
            'event_type' => $type,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function listEvents(int $webinarId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT second_from_start, event_type, payload_json FROM webinar_events WHERE webinar_id = :webinar_id ORDER BY second_from_start ASC'
        );
        $stmt->execute(['webinar_id' => $webinarId]);

        $events = [];
        foreach ($stmt->fetchAll() as $row) {
            $events[] = [
                'at' => (int) $row['second_from_start'],
                'type' => (string) $row['event_type'],
                'payload' => json_decode((string) $row['payload_json'], true) ?: [],
            ];
        }

        return $events;
    }
}
