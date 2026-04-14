<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProcessTemplateRepository extends BaseRepository
{
    public function list(int $accountId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM process_template_library WHERE account_id=:account_id ORDER BY id DESC');
        $stmt->execute(['account_id' => $accountId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo()->prepare('INSERT INTO process_template_library (account_id, project_id, name, slug, description, graph_json, compiled_graph_json, meta_json, status, version_number, created_by, created_at, updated_at) VALUES (:account_id,:project_id,:name,:slug,:description,:graph_json,:compiled_graph_json,:meta_json,:status,1,:created_by,NOW(),NOW())');
        $stmt->execute([
            'account_id' => $data['account_id'],
            'project_id' => $data['project_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'graph_json' => json_encode($data['graph_json'] ?? ['nodes' => [], 'edges' => []], JSON_UNESCAPED_UNICODE),
            'compiled_graph_json' => json_encode($data['compiled_graph_json'] ?? null, JSON_UNESCAPED_UNICODE),
            'meta_json' => json_encode($data['meta_json'] ?? null, JSON_UNESCAPED_UNICODE),
            'status' => $data['status'] ?? 'draft',
            'created_by' => $data['created_by'],
        ]);

        return $this->find((int)$this->pdo()->lastInsertId()) ?? [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM process_template_library WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
