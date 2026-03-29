<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\AnalyticsRepository;
use App\Models\AuditLogRepository;
use App\Models\WebinarRepository;
use App\Services\AdminAuthService;
use App\Services\AnalyticsAttributionService;

final class AnalyticsController
{
    public function createDataSlice(): void
    {
        (new AdminAuthService())->requireAdmin();
        $payload = $this->readJsonBody();
        $externalId = (string) ($payload['webinar_id'] ?? '');
        $label = (string) ($payload['label'] ?? 'manual_slice');

        if ($externalId === '') {
            Response::json(['error' => 'webinar_id обязателен'], 422);
            return;
        }

        $webinarRepo = new WebinarRepository();
        $webinar = $webinarRepo->findByExternalId($externalId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $repo = new AnalyticsRepository();
        $onlineCount = $repo->createDataSlice((int) $webinar['id'], $label);

        (new AuditLogRepository())->write('admin_api', 'data_slice_created', ['webinar_id' => $externalId, 'label' => $label]);

        Response::json([
            'message' => 'Data Slice создан',
            'label' => $label,
            'online_count' => $onlineCount,
        ]);
    }

    public function saveUtmSpend(): void
    {
        (new AdminAuthService())->requireAdmin();
        $payload = $this->readJsonBody();
        $externalId = (string) ($payload['webinar_id'] ?? '');
        $source = (string) ($payload['utm_source'] ?? '');
        $medium = (string) ($payload['utm_medium'] ?? '');
        $campaign = (string) ($payload['utm_campaign'] ?? '');
        $spend = (int) ($payload['spend_cents'] ?? 0);

        $webinar = (new WebinarRepository())->findByExternalId($externalId);
        if ($webinar === null || $source === '' || $medium === '' || $campaign === '') {
            Response::json(['error' => 'Некорректные входные данные'], 422);
            return;
        }

        (new AnalyticsRepository())->saveUtmSpend((int) $webinar['id'], $source, $medium, $campaign, $spend);
        Response::json(['ok' => true]);
    }

    public function attributionReport(): void
    {
        (new AdminAuthService())->requireAdmin();
        $externalId = (string) ($_GET['webinar_id'] ?? '');
        $webinar = (new WebinarRepository())->findByExternalId($externalId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $rows = (new AnalyticsRepository())->attributionRows((int) $webinar['id']);
        $report = (new AnalyticsAttributionService())->buildAttribution($rows);
        Response::json(['report' => $report]);
    }

    public function recordInsightReady(): void
    {
        (new AdminAuthService())->requireAdmin();
        $payload = $this->readJsonBody();
        $externalId = (string) ($payload['webinar_id'] ?? '');
        $finishedAt = (string) ($payload['finished_at'] ?? '');
        $readyAt = (string) ($payload['insight_ready_at'] ?? '');

        $webinar = (new WebinarRepository())->findByExternalId($externalId);
        if ($webinar === null || $finishedAt === '' || $readyAt === '') {
            Response::json(['error' => 'Некорректные входные данные'], 422);
            return;
        }

        (new AnalyticsRepository())->recordInsightReady((int) $webinar['id'], $finishedAt, $readyAt);
        Response::json(['ok' => true]);
    }

    public function insightMonitoring(): void
    {
        (new AdminAuthService())->requireAdmin();
        $externalId = (string) ($_GET['webinar_id'] ?? '');
        $webinar = (new WebinarRepository())->findByExternalId($externalId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $rows = (new AnalyticsRepository())->insightMonitoringRows((int) $webinar['id']);
        $svc = new AnalyticsAttributionService();
        $withTti = array_map(static function (array $row) use ($svc): array {
            $row['time_to_insight_min'] = $svc->timeToInsightMinutes((string) $row['finished_at'], (string) $row['insight_ready_at']);
            return $row;
        }, $rows);

        Response::json(['insights' => $withTti]);
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
