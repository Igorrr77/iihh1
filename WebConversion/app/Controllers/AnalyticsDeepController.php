<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\AnalyticsEventRepository;
use App\Models\WebinarRepository;

final class AnalyticsDeepController
{
    public function trackEvent(): void
    {
        $payload = $this->readJsonBody();
        $webinarId = (string) ($payload['webinar_id'] ?? '');
        $leadToken = (string) ($payload['lead_token'] ?? '');
        $eventType = (string) ($payload['event_type'] ?? 'heartbeat');
        $second = (int) ($payload['second_from_start'] ?? 0);
        $utm = (array) ($payload['utm'] ?? []);

        $webinar = (new WebinarRepository())->findByExternalId($webinarId);
        if ($webinar === null || $leadToken === '') {
            Response::json(['error' => 'Неверные входные данные'], 422);
            return;
        }

        (new AnalyticsEventRepository())->track((int) $webinar['id'], $leadToken, $eventType, $second, $utm);
        Response::json(['ok' => true]);
    }

    public function heatmap(): void
    {
        $webinarId = (string) ($_GET['webinar_id'] ?? '');
        $webinar = (new WebinarRepository())->findByExternalId($webinarId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $data = (new AnalyticsEventRepository())->retentionHeatmap((int) $webinar['id']);
        Response::json(['heatmap' => $data]);
    }

    public function exportCsv(): void
    {
        $webinarId = (string) ($_GET['webinar_id'] ?? '');
        $limit = (int) ($_GET['limit'] ?? 1000);

        $webinar = (new WebinarRepository())->findByExternalId($webinarId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $events = (new AnalyticsEventRepository())->listEvents((int) $webinar['id'], $limit);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="analytics-' . $webinarId . '.csv"');

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            Response::json(['error' => 'Не удалось сформировать CSV'], 500);
            return;
        }

        fputcsv($out, ['lead_token', 'event_type', 'second_from_start', 'utm_source', 'utm_medium', 'utm_campaign', 'created_at']);
        foreach ($events as $row) {
            fputcsv($out, [
                $row['lead_token'] ?? '',
                $row['event_type'] ?? '',
                $row['second_from_start'] ?? 0,
                $row['utm_source'] ?? '',
                $row['utm_medium'] ?? '',
                $row['utm_campaign'] ?? '',
                $row['created_at'] ?? '',
            ]);
        }

        fclose($out);
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
