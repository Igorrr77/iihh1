<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProjectRepository extends BaseRepository
{
    public function allByAccount(int $accountId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM projects WHERE account_id = :account_id ORDER BY id DESC');
        $stmt->execute(['account_id' => $accountId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id, int $accountId): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM projects WHERE id = :id AND account_id = :account_id LIMIT 1');
        $stmt->execute(['id' => $id, 'account_id' => $accountId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo()->prepare('INSERT INTO projects (account_id, name, slug, description, status, created_by, created_at, updated_at) VALUES (:account_id, :name, :slug, :description, :status, :created_by, NOW(), NOW())');
        $stmt->execute([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => $data['created_by'],
        ]);

        return $this->findById((int)$this->pdo()->lastInsertId(), (int)$data['account_id']) ?? [];
    }

    public function update(int $id, int $accountId, array $data): ?array
    {
        $stmt = $this->pdo()->prepare('UPDATE projects SET name = :name, description = :description, status = :status, updated_at = NOW() WHERE id = :id AND account_id = :account_id');
        $stmt->execute([
            'id' => $id,
            'account_id' => $accountId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        return $this->findById($id, $accountId);
    }
}
