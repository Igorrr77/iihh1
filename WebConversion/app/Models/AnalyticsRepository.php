<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AnalyticsRepository
{
    public function createDataSlice(int $webinarId, string $label): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO data_slices (webinar_id, slice_label, online_count, created_at) VALUES (:webinar_id, :slice_label, :online_count, NOW())');

        $onlineStmt = $pdo->prepare("SELECT COUNT(*) AS online_count FROM attendance_events WHERE webinar_id = :webinar_id AND event_type = 'join'");
        $onlineStmt->execute(['webinar_id' => $webinarId]);
        $onlineCount = (int) (($onlineStmt->fetch()['online_count'] ?? 0));

        $stmt->execute([
            'webinar_id' => $webinarId,
            'slice_label' => $label,
            'online_count' => $onlineCount,
        ]);

        return $onlineCount;
    }

    public function attributionRows(int $webinarId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT
                ae.utm_source,
                ae.utm_medium,
                ae.utm_campaign,
                COUNT(DISTINCT ae.lead_token) AS leads,
                COALESCE(SUM(CASE WHEN p.status = "paid" THEN p.amount_cents ELSE 0 END), 0) AS revenue_cents,
                COALESCE(MAX(us.spend_cents), 0) AS spend_cents
             FROM analytics_events ae
             LEFT JOIN payments p ON p.webinar_id = ae.webinar_id AND p.lead_token = ae.lead_token
             LEFT JOIN utm_spend us ON us.webinar_id = ae.webinar_id
                AND us.utm_source = COALESCE(ae.utm_source, "")
                AND us.utm_medium = COALESCE(ae.utm_medium, "")
                AND us.utm_campaign = COALESCE(ae.utm_campaign, "")
             WHERE ae.webinar_id = :webinar_id
             GROUP BY ae.utm_source, ae.utm_medium, ae.utm_campaign'
        );
        $stmt->execute(['webinar_id' => $webinarId]);
        return $stmt->fetchAll() ?: [];
    }

    public function saveUtmSpend(int $webinarId, string $source, string $medium, string $campaign, int $spendCents): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO utm_spend (webinar_id, utm_source, utm_medium, utm_campaign, spend_cents)
             VALUES (:webinar_id,:utm_source,:utm_medium,:utm_campaign,:spend_cents)'
        );
        $stmt->execute([
            'webinar_id' => $webinarId,
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
            'spend_cents' => max(0, $spendCents),
        ]);
    }

    public function recordInsightReady(int $webinarId, string $finishedAt, string $insightReadyAt): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO insight_monitoring (webinar_id, finished_at, insight_ready_at) VALUES (:webinar_id,:finished_at,:insight_ready_at)');
        $stmt->execute([
            'webinar_id' => $webinarId,
            'finished_at' => $finishedAt,
            'insight_ready_at' => $insightReadyAt,
        ]);
    }

    public function insightMonitoringRows(int $webinarId, int $limit = 50): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT finished_at, insight_ready_at, created_at FROM insight_monitoring WHERE webinar_id = :webinar_id ORDER BY id DESC LIMIT :lim');
        $stmt->bindValue(':webinar_id', $webinarId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
