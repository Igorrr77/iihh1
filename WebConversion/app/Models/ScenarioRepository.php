<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ScenarioRepository
{
    public function save(int $webinarId, array $scenario): int
    {
        return $this->saveVersion($webinarId, $scenario, 'draft', null, null);
    }

    public function saveVersion(int $webinarId, array $scenario, string $status = 'draft', ?int $sourceVersion = null, ?string $migrationTag = null): int
    {
        $pdo = Database::connection();

        $versionStmt = $pdo->prepare('SELECT COALESCE(MAX(version), 0) AS max_version FROM webinar_scenarios WHERE webinar_id = :webinar_id');
        $versionStmt->execute(['webinar_id' => $webinarId]);
        $maxVersion = (int) ($versionStmt->fetch()['max_version'] ?? 0);
        $version = $maxVersion + 1;

        $stmt = $pdo->prepare(
            'INSERT INTO webinar_scenarios (webinar_id, version, scenario_json, status, source_version, migration_tag, published_at)
             VALUES (:webinar_id, :version, :scenario_json, :status, :source_version, :migration_tag, :published_at)'
        );
        $stmt->execute([
            'webinar_id' => $webinarId,
            'version' => $version,
            'scenario_json' => json_encode($scenario, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => $status,
            'source_version' => $sourceVersion,
            'migration_tag' => $migrationTag,
            'published_at' => $status === 'published' ? gmdate('Y-m-d H:i:s') : null,
        ]);

        return $version;
    }

    public function publishVersion(int $webinarId, int $version): bool
    {
        $pdo = Database::connection();
        $check = $pdo->prepare('SELECT id FROM webinar_scenarios WHERE webinar_id = :webinar_id AND version = :version LIMIT 1');
        $check->execute(['webinar_id' => $webinarId, 'version' => $version]);
        if (!is_array($check->fetch())) {
            return false;
        }

        $pdo->prepare('UPDATE webinar_scenarios SET status = "archived" WHERE webinar_id = :webinar_id AND status = "published"')
            ->execute(['webinar_id' => $webinarId]);

        $pdo->prepare('UPDATE webinar_scenarios SET status = "published", published_at = UTC_TIMESTAMP() WHERE webinar_id = :webinar_id AND version = :version')
            ->execute(['webinar_id' => $webinarId, 'version' => $version]);

        return true;
    }

    public function rollbackToVersion(int $webinarId, int $version): ?int
    {
        $source = $this->byVersion($webinarId, $version);
        if ($source === null) {
            return null;
        }

        return $this->saveVersion($webinarId, $source, 'published', $version, 'rollback');
    }

    public function listVersions(int $webinarId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT version, status, source_version, migration_tag, published_at, created_at FROM webinar_scenarios WHERE webinar_id = :webinar_id ORDER BY version DESC LIMIT 100');
        $stmt->execute(['webinar_id' => $webinarId]);
        return $stmt->fetchAll() ?: [];
    }

    public function latest(int $webinarId): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT scenario_json FROM webinar_scenarios WHERE webinar_id = :webinar_id ORDER BY version DESC LIMIT 1');
        $stmt->execute(['webinar_id' => $webinarId]);
        $row = $stmt->fetch();

        if (!is_array($row) || !isset($row['scenario_json'])) {
            return null;
        }

        $decoded = json_decode((string) $row['scenario_json'], true);
        return is_array($decoded) ? $decoded : null;
    }

    public function byVersion(int $webinarId, int $version): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT scenario_json FROM webinar_scenarios WHERE webinar_id = :webinar_id AND version = :version LIMIT 1');
        $stmt->execute(['webinar_id' => $webinarId, 'version' => $version]);
        $row = $stmt->fetch();

        if (!is_array($row) || !isset($row['scenario_json'])) {
            return null;
        }

        $decoded = json_decode((string) $row['scenario_json'], true);
        return is_array($decoded) ? $decoded : null;
    }

    public function logImportExport(int $webinarId, string $operation, string $adapter, array $payload): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO scenario_import_exports (webinar_id, operation, adapter, payload_json) VALUES (:webinar_id, :operation, :adapter, :payload_json)'
        );
        $stmt->execute([
            'webinar_id' => $webinarId,
            'operation' => $operation,
            'adapter' => $adapter,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
