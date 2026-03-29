<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\SchedulerRepository;
use App\Models\WebinarRepository;
use App\Services\AdminAuthService;
use App\Services\JitSchedulerService;

final class SchedulerController
{
    public function createSession(): void
    {
        (new AdminAuthService())->requireAdmin();
        $payload = $this->readJsonBody();

        $externalId = (string) ($payload['webinar_id'] ?? '');
        $mode = (string) ($payload['mode'] ?? 'instant');
        $timezone = (string) ($payload['timezone'] ?? 'UTC');
        $fixedStart = isset($payload['fixed_start_utc']) ? (string) $payload['fixed_start_utc'] : null;
        $fixedStartLocal = isset($payload['fixed_start_local']) ? (string) $payload['fixed_start_local'] : null;

        $webinar = (new WebinarRepository())->findByExternalId($externalId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $startAt = (new JitSchedulerService())->calculateStartAt($mode, $timezone, $fixedStart, $fixedStartLocal);
        $sessionId = (new SchedulerRepository())->createSession((int) $webinar['id'], $mode, $timezone, $startAt);

        Response::json(['session_id' => $sessionId, 'start_at_utc' => $startAt], 201);
    }

    public function nextSession(): void
    {
        $webinarId = (string) ($_GET['webinar_id'] ?? '');
        $webinar = (new WebinarRepository())->findByExternalId($webinarId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $session = (new SchedulerRepository())->nextForWebinar((int) $webinar['id']);
        Response::json(['next_session' => $session]);
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
