<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\ScenarioRepository;
use App\Models\WebinarRepository;
use App\Services\RbacAuthService;
use App\Services\ScenarioMacroCompiler;
use App\Services\ScenarioService;

final class ScenarioController
{
    public function compileMacros(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $scenario = (array) ($payload['scenario'] ?? []);

        $compiled = (new ScenarioMacroCompiler())->compile($scenario);
        Response::json(['compiled' => $compiled]);
    }

    public function validate(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $scenario = (array) ($payload['scenario'] ?? []);
        Response::json((new ScenarioService())->validate($scenario));
    }

    public function saveDraft(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $webinar = $this->resolveWebinar((string) ($payload['webinar_id'] ?? ''));
        if ($webinar === null) {
            return;
        }

        $scenario = (array) ($payload['scenario'] ?? []);
        $validation = (new ScenarioService())->validate($scenario);
        if (!(bool) ($validation['ok'] ?? false)) {
            Response::json($validation, 422);
            return;
        }

        $version = (new ScenarioRepository())->saveVersion((int) $webinar['id'], $scenario, 'draft');
        Response::json(['ok' => true, 'version' => $version], 201);
    }

    public function publish(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $webinar = $this->resolveWebinar((string) ($payload['webinar_id'] ?? ''));
        if ($webinar === null) {
            return;
        }

        $version = (int) ($payload['version'] ?? 0);
        if ($version <= 0) {
            Response::json(['error' => 'version обязателен'], 422);
            return;
        }

        $ok = (new ScenarioRepository())->publishVersion((int) $webinar['id'], $version);
        if (!$ok) {
            Response::json(['error' => 'Версия не найдена'], 404);
            return;
        }

        Response::json(['ok' => true]);
    }

    public function rollback(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $webinar = $this->resolveWebinar((string) ($payload['webinar_id'] ?? ''));
        if ($webinar === null) {
            return;
        }

        $version = (int) ($payload['to_version'] ?? 0);
        if ($version <= 0) {
            Response::json(['error' => 'to_version обязателен'], 422);
            return;
        }

        $newVersion = (new ScenarioRepository())->rollbackToVersion((int) $webinar['id'], $version);
        if ($newVersion === null) {
            Response::json(['error' => 'Версия для rollback не найдена'], 404);
            return;
        }

        Response::json(['ok' => true, 'new_version' => $newVersion]);
    }

    public function versions(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $webinar = $this->resolveWebinar((string) ($_GET['webinar_id'] ?? ''));
        if ($webinar === null) {
            return;
        }

        $versions = (new ScenarioRepository())->listVersions((int) $webinar['id']);
        Response::json(['versions' => $versions]);
    }

    public function preview(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $scenario = (array) ($payload['scenario'] ?? []);
        $compiled = (new ScenarioMacroCompiler())->compile($scenario);
        Response::json(['preview_events' => $compiled['events'] ?? []]);
    }

    public function importAdapter(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $adapter = (string) ($payload['adapter'] ?? 'native');
        $webinar = $this->resolveWebinar((string) ($payload['webinar_id'] ?? ''));
        if ($webinar === null) {
            return;
        }

        $scenario = (new ScenarioService())->importAdapter($adapter, (array) ($payload['payload'] ?? []));
        (new ScenarioRepository())->logImportExport((int) $webinar['id'], 'import', $adapter, $scenario);

        Response::json(['scenario' => $scenario]);
    }

    public function exportAdapter(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $adapter = (string) ($payload['adapter'] ?? 'native');
        $webinar = $this->resolveWebinar((string) ($payload['webinar_id'] ?? ''));
        if ($webinar === null) {
            return;
        }

        $version = (int) ($payload['version'] ?? 0);
        $scenario = $version > 0
            ? (new ScenarioRepository())->byVersion((int) $webinar['id'], $version)
            : (new ScenarioRepository())->latest((int) $webinar['id']);

        if ($scenario === null) {
            Response::json(['error' => 'Сценарий не найден'], 404);
            return;
        }

        $exported = (new ScenarioService())->exportAdapter($adapter, $scenario);
        (new ScenarioRepository())->logImportExport((int) $webinar['id'], 'export', $adapter, $exported);
        Response::json(['exported' => $exported]);
    }

    public function diffVersions(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();

        $webinarExternalId = (string) ($payload['webinar_id'] ?? '');
        $leftVersion = (int) ($payload['left_version'] ?? 0);
        $rightVersion = (int) ($payload['right_version'] ?? 0);

        $webinar = (new WebinarRepository())->findByExternalId($webinarExternalId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $repo = new ScenarioRepository();
        $left = $repo->byVersion((int) $webinar['id'], $leftVersion);
        $right = $repo->byVersion((int) $webinar['id'], $rightVersion);

        if ($left === null || $right === null) {
            Response::json(['error' => 'Одна из версий не найдена'], 404);
            return;
        }

        $diff = (new ScenarioMacroCompiler())->diff($left, $right);
        Response::json(['diff' => $diff]);
    }

    private function resolveWebinar(string $externalId): ?array
    {
        $webinar = (new WebinarRepository())->findByExternalId($externalId);
        if ($externalId === '' || $webinar === null) {
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
