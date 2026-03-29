<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\AuditLogRepository;
use App\Models\ScenarioRepository;
use App\Models\WebinarRepository;
use App\Services\AdminAuthService;
use App\Services\AiScenarioGenerator;
use App\Services\ScenarioService;

final class WebinarController
{
    public function store(): void
    {
        (new AdminAuthService())->requireAdmin();
        $payload = $this->readJsonBody();

        $required = ['title', 'format', 'timezone'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                Response::json(['error' => "Поле {$field} обязательно"], 422);
                return;
            }
        }

        $externalId = 'wb_' . bin2hex(random_bytes(6));
        $accessMode = (string) ($payload['access_mode'] ?? 'name_email_phone');

        $repo = new WebinarRepository();
        $webinar = $repo->create(
            $externalId,
            (string) $payload['title'],
            (string) $payload['format'],
            (string) $payload['timezone'],
            $accessMode
        );

        (new AuditLogRepository())->write('admin_api', 'webinar_created', ['external_id' => $externalId]);

        Response::json([
            'message' => 'Вебинар создан',
            'webinar' => $webinar,
        ], 201);
    }

    public function importScenario(): void
    {
        (new AdminAuthService())->requireAdmin();
        $payload = $this->readJsonBody();
        $externalId = isset($payload['webinar_id']) ? (string) $payload['webinar_id'] : '';
        $json = isset($payload['scenario_json']) ? (string) $payload['scenario_json'] : '';

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

        $service = new ScenarioService();
        $result = $service->importFromJson($json);
        if (($result['ok'] ?? false) !== true) {
            Response::json($result, 422);
            return;
        }

        $decoded = json_decode($json, true);
        $scenarioRepo = new ScenarioRepository();
        $scenarioRepo->save((int) $webinar['id'], is_array($decoded) ? $decoded : []);

        (new AuditLogRepository())->write('admin_api', 'scenario_imported', ['webinar_id' => $externalId]);

        Response::json($result, 200);
    }

    public function exportScenario(): void
    {
        $externalId = (string) ($_GET['webinar_id'] ?? '');
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

        $scenarioRepo = new ScenarioRepository();
        $scenario = $scenarioRepo->latest((int) $webinar['id']);
        if ($scenario === null) {
            $service = new ScenarioService();
            Response::json($service->exportTemplate($externalId));
            return;
        }

        Response::json($scenario);
    }

    public function generateAiScenario(): void
    {
        (new AdminAuthService())->requireAdmin();
        $payload = $this->readJsonBody();
        $transcript = isset($payload['transcript']) ? (string) $payload['transcript'] : '';

        if ($transcript === '') {
            Response::json(['error' => 'transcript обязателен'], 422);
            return;
        }

        $viewers = isset($payload['virtual_viewers']) ? (int) $payload['virtual_viewers'] : 200;
        $generator = new AiScenarioGenerator();

        $scenario = $generator->generate($transcript, $viewers);
        (new AuditLogRepository())->write('admin_api', 'ai_scenario_generated', ['virtual_viewers' => $viewers]);

        Response::json([
            'message' => 'AI-сценарий сгенерирован',
            'scenario' => $scenario,
        ]);
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
