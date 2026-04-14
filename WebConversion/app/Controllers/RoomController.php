<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\RoomAccessRepository;
use App\Models\WebinarRepository;
use App\Services\AccessPolicyService;
use App\Services\RateLimiterService;

final class RoomController
{
    public function register(): void
    {
        $payload = $this->readJsonBody();
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $rateLimiter = new RateLimiterService();
        if (!$rateLimiter->hit('room_register', $ip, 20)) {
            Response::json(['error' => 'Слишком много запросов. Попробуйте через минуту.'], 429);
            return;
        }

        $externalId = (string) ($payload['webinar_id'] ?? '');

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

        $policy = new AccessPolicyService();
        $validated = $policy->validate((string) $webinar['access_mode'], $payload);
        if (($validated['ok'] ?? false) !== true) {
            Response::json(['error' => $validated['error'] ?? 'Ошибка валидации'], 422);
            return;
        }

        $repo = new RoomAccessRepository();
        $token = $repo->registerLead(
            (int) $webinar['id'],
            (string) $validated['name'],
            $validated['email'],
            $validated['phone']
        );

        $repo->logAttendance((int) $webinar['id'], $token, 'join');

        Response::json([
            'message' => 'Доступ в комнату выдан',
            'access_token' => $token,
        ], 201);
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
