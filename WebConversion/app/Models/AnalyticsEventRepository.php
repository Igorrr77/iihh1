<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AnalyticsEventRepository
{
    public function track(int $webinarId, string $leadToken, string $eventType, int $second, array $utm): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO analytics_events (webinar_id, lead_token, event_type, second_from_start, utm_source, utm_medium, utm_campaign) VALUES (:webinar_id,:lead_token,:event_type,:second_from_start,:utm_source,:utm_medium,:utm_campaign)'
        );
        $stmt->execute([
            'webinar_id' => $webinarId,
            'lead_token' => $leadToken,
            'event_type' => $eventType,
            'second_from_start' => $second,
            'utm_source' => $utm['source'] ?? null,
            'utm_medium' => $utm['medium'] ?? null,
            'utm_campaign' => $utm['campaign'] ?? null,
        ]);
    }

    public function retentionHeatmap(int $webinarId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT second_from_start, COUNT(*) AS events_count FROM analytics_events WHERE webinar_id = :webinar_id AND event_type = :event_type GROUP BY second_from_start ORDER BY second_from_start ASC'
        );
        $stmt->execute(['webinar_id' => $webinarId, 'event_type' => 'heartbeat']);
        return $stmt->fetchAll() ?: [];
    }

    public function listEvents(int $webinarId, int $limit = 1000): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT lead_token, event_type, second_from_start, utm_source, utm_medium, utm_campaign, created_at
             FROM analytics_events
             WHERE webinar_id = :webinar_id
             ORDER BY id ASC
             LIMIT :lim'
        );
        $stmt->bindValue(':webinar_id', $webinarId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', max(1, min($limit, 5000)), \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }
}
