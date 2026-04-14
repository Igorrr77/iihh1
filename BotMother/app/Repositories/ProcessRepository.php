<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProcessRepository extends BaseRepository
{
    public function findProcess(int $id, int $accountId): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM processes WHERE id=:id AND account_id=:account_id LIMIT 1');
        $stmt->execute(['id' => $id, 'account_id' => $accountId]);
        return $stmt->fetch() ?: null;
    }

    public function createVersion(int $processId, int $userId, array $graph): array
    {
        $stmt = $this->pdo()->prepare('SELECT COALESCE(MAX(version_number), 0) + 1 FROM process_versions WHERE process_id=:process_id');
        $stmt->execute(['process_id' => $processId]);
        $version = (int)$stmt->fetchColumn();

        $insert = $this->pdo()->prepare('INSERT INTO process_versions (process_id, version_number, status, graph_json, graph_hash, validation_status, created_by, created_at, updated_at) VALUES (:process_id,:version_number,"draft",:graph_json,:graph_hash,"invalid",:created_by,NOW(),NOW())');
        $insert->execute([
            'process_id' => $processId,
            'version_number' => $version,
            'graph_json' => json_encode($graph, JSON_UNESCAPED_UNICODE),
            'graph_hash' => hash('sha256', json_encode($graph)),
            'created_by' => $userId,
        ]);

        return $this->findVersion((int)$this->pdo()->lastInsertId()) ?? [];
    }

    public function findVersion(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM process_versions WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function updateVersion(int $id, array $graph): ?array
    {
        $stmt = $this->pdo()->prepare('UPDATE process_versions SET graph_json=:graph_json, graph_hash=:graph_hash, status="draft", updated_at=NOW() WHERE id=:id');
        $stmt->execute([
            'id' => $id,
            'graph_json' => json_encode($graph, JSON_UNESCAPED_UNICODE),
            'graph_hash' => hash('sha256', json_encode($graph)),
        ]);
        return $this->findVersion($id);
    }

    public function saveValidation(int $id, string $status, array $errors, array $warnings): void
    {
        $stmt = $this->pdo()->prepare('UPDATE process_versions SET validation_status=:validation_status, validation_errors_json=:errors, updated_at=NOW() WHERE id=:id');
        $stmt->execute([
            'id' => $id,
            'validation_status' => $status,
            'errors' => json_encode(['errors' => $errors, 'warnings' => $warnings], JSON_UNESCAPED_UNICODE),
        ]);
    }


    public function saveCompiled(int $id, array $compiled): void
    {
        $stmt = $this->pdo()->prepare('UPDATE process_versions SET compiled_graph_json=:compiled_graph_json, validation_status="valid", updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $id, 'compiled_graph_json' => json_encode($compiled, JSON_UNESCAPED_UNICODE)]);
    }

    public function publishVersion(int $versionId): ?array
    {
        $version = $this->findVersion($versionId);
        if (!$version) {
            return null;
        }

        $this->pdo()->beginTransaction();
        try {
            $stmt = $this->pdo()->prepare('UPDATE process_versions SET status="published", published_at=NOW(), updated_at=NOW() WHERE id=:id');
            $stmt->execute(['id' => $versionId]);

            $stmt = $this->pdo()->prepare('UPDATE processes SET active_version_id=:version_id, status="published", updated_at=NOW() WHERE id=:process_id');
            $stmt->execute(['version_id' => $versionId, 'process_id' => $version['process_id']]);

            $this->pdo()->commit();
        } catch (\Throwable $e) {
            $this->pdo()->rollBack();
            throw $e;
        }

        return $this->findVersion($versionId);
    }
}
