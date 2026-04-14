<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\AdminAuthService;
use App\Models\AuditLogRepository;
use App\Models\TimelineRepository;
use App\Models\WebinarRepository;

final class TimelineController
{
    public function addEvent(): void
    {
        (new AdminAuthService())->requireAdmin();
        $payload = $this->readJsonBody();
        $webinar = $this->resolveWebinar($payload);
        if ($webinar === null) {
            return;
        }

        $second = isset($payload['at']) ? (int) $payload['at'] : -1;
        $type = (string) ($payload['type'] ?? '');

        if ($second < 0 || $type === '') {
            Response::json(['error' => 'Поля at (>=0) и type обязательны'], 422);
            return;
        }

        $repo = new TimelineRepository();
        $repo->addEvent((int) $webinar['id'], $second, $type, (array) ($payload['payload'] ?? []));

        (new AuditLogRepository())->write('admin_api', 'timeline_event_added', ['webinar_id' => $webinar['external_id'], 'type' => $type]);

        Response::json(['message' => 'Событие добавлено']);
    }

    public function listEvents(): void
    {
        (new AdminAuthService())->requireAdmin();
        $payload = $this->readJsonBody();
        $webinar = $this->resolveWebinar($payload);
        if ($webinar === null) {
            return;
        }

        $repo = new TimelineRepository();
        Response::json([
            'webinar_id' => $webinar['external_id'],
            'events' => $repo->listEvents((int) $webinar['id']),
        ]);
    }

    private function resolveWebinar(array $payload): ?array
    {
        $externalId = (string) ($payload['webinar_id'] ?? '');
        if ($externalId === '') {
            Response::json(['error' => 'webinar_id обязателен'], 422);
            return null;
        }

        $repo = new WebinarRepository();
        $webinar = $repo->findByExternalId($externalId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return null;
        }

        return $webinar;
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
