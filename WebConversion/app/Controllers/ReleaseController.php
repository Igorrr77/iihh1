<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\FeatureFlagRepository;
use App\Models\GaGateRepository;
use App\Services\FeatureFlagService;
use App\Services\RbacAuthService;
use App\Services\GaStabilizationService;
use App\Services\ReleasePolicyService;
use App\Services\RoomReadinessService;

final class ReleaseController
{
    public function listFlags(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $flags = (new FeatureFlagRepository())->all();
        Response::json(['flags' => $flags]);
    }

    public function setFlag(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $key = (new FeatureFlagService())->normalizeKey((string) ($payload['flag_key'] ?? ''));
        $enabled = (bool) ($payload['enabled'] ?? false);

        if ($key === '') {
            Response::json(['error' => 'flag_key обязателен'], 422);
            return;
        }

        (new FeatureFlagRepository())->upsert($key, $enabled);
        Response::json(['ok' => true, 'flag_key' => $key, 'enabled' => $enabled]);
    }

    public function setStageStatus(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $code = strtoupper((string) ($payload['stage_code'] ?? ''));
        $name = (string) ($payload['stage_name'] ?? '');
        $completed = (bool) ($payload['is_completed'] ?? false);

        if (!in_array($code, range('A', 'H'), true) || $name === '') {
            Response::json(['error' => 'stage_code A-H и stage_name обязательны'], 422);
            return;
        }

        (new GaGateRepository())->setStage($code, $name, $completed, (string) ($payload['owner'] ?? ''), (string) ($payload['notes'] ?? ''));
        Response::json(['ok' => true]);
    }

    public function listStages(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        Response::json(['stages' => (new GaGateRepository())->listStages()]);
    }

    public function setSla(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $metric = (string) ($payload['metric_key'] ?? '');
        $target = (string) ($payload['target_value'] ?? '');
        $owner = (string) ($payload['owner_on_call'] ?? '');
        $dashboard = (string) ($payload['dashboard_url'] ?? '');
        $runbook = (string) ($payload['runbook_url'] ?? '');

        if ($metric === '' || $target === '' || $owner === '' || $dashboard === '' || $runbook === '') {
            Response::json(['error' => 'metric_key,target_value,owner_on_call,dashboard_url,runbook_url обязательны'], 422);
            return;
        }

        (new GaGateRepository())->upsertSla($metric, $target, $owner, $dashboard, $runbook);
        Response::json(['ok' => true]);
    }

    public function listSla(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        Response::json(['sla' => (new GaGateRepository())->listSla()]);
    }

    public function addIncident(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $severity = (string) ($payload['severity'] ?? 'low');
        $summary = (string) ($payload['summary'] ?? '');
        $started = (string) ($payload['started_at'] ?? gmdate('Y-m-d H:i:s'));
        $resolved = isset($payload['resolved_at']) ? (string) $payload['resolved_at'] : null;

        if ($summary === '' || !in_array($severity, ['critical', 'high', 'medium', 'low'], true)) {
            Response::json(['error' => 'Некорректный incident payload'], 422);
            return;
        }

        (new GaGateRepository())->addIncident($severity, $started, $resolved, $summary);
        Response::json(['ok' => true]);
    }

    public function goNoGoReview(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $decision = (string) ($payload['decision'] ?? 'no_go');
        $reviewer = (string) ($payload['reviewer'] ?? 'release_manager');
        $notes = (string) ($payload['notes'] ?? '');

        if (!in_array($decision, ['go', 'no_go'], true)) {
            Response::json(['error' => 'decision must be go|no_go'], 422);
            return;
        }

        (new GaGateRepository())->addGoNoGo($decision, $reviewer, $notes);
        Response::json(['ok' => true]);
    }

    public function gaGateStatus(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        Response::json((new GaGateRepository())->gaGateStatus());
    }

    public function roomReadiness(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        Response::json((new RoomReadinessService())->buildSnapshot());
    }


    public function policyGate(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);

        $input = $this->readJsonBody();
        $slo = [
            'chat_latency_p95_ms' => $input['chat_latency_p95_ms'] ?? ($_GET['chat_latency_p95_ms'] ?? null),
            'payment_error_rate' => $input['payment_error_rate'] ?? ($_GET['payment_error_rate'] ?? null),
            'runtime_error_rate' => $input['runtime_error_rate'] ?? ($_GET['runtime_error_rate'] ?? null),
        ];
        $slo = array_filter($slo, static fn ($v): bool => $v !== null && $v !== '');

        Response::json((new ReleasePolicyService())->buildFromRepositories($slo));
    }


    public function gaPassport(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        Response::json((new GaStabilizationService())->buildFromSystem());
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
