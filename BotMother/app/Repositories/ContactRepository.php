<?php

declare(strict_types=1);

namespace App\Repositories;

final class ContactRepository extends BaseRepository
{
    public function list(int $accountId, int $projectId = 0): array
    {
        $sql = 'SELECT * FROM contacts WHERE account_id = :account_id';
        $params = ['account_id' => $accountId];
        if ($projectId > 0) {
            $sql .= ' AND project_id = :project_id';
            $params['project_id'] = $projectId;
        }
        $sql .= ' ORDER BY id DESC LIMIT 200';

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $accountId): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM contacts WHERE id=:id AND account_id=:account_id LIMIT 1');
        $stmt->execute(['id' => $id, 'account_id' => $accountId]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, int $accountId, array $data): ?array
    {
        $stmt = $this->pdo()->prepare('UPDATE contacts SET first_name=:first_name,last_name=:last_name,phone=:phone,email=:email,status=:status,updated_at=NOW() WHERE id=:id AND account_id=:account_id');
        $stmt->execute([
            'id' => $id,
            'account_id' => $accountId,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
        return $this->find($id, $accountId);
    }

    public function addTag(int $contactId, int $projectId, string $tagCode, int $userId): void
    {
        $tagStmt = $this->pdo()->prepare('SELECT id FROM tags WHERE project_id=:project_id AND code=:code LIMIT 1');
        $tagStmt->execute(['project_id' => $projectId, 'code' => $tagCode]);
        $tagId = $tagStmt->fetchColumn();

        if (!$tagId) {
            $insert = $this->pdo()->prepare('INSERT INTO tags (account_id, project_id, code, name, created_at, updated_at) VALUES (1,:project_id,:code,:name,NOW(),NOW())');
            $insert->execute(['project_id' => $projectId, 'code' => $tagCode, 'name' => ucfirst(str_replace('_', ' ', $tagCode))]);
            $tagId = (int)$this->pdo()->lastInsertId();
        }

        $stmt = $this->pdo()->prepare('INSERT IGNORE INTO contact_tags (contact_id, tag_id, created_at, created_by) VALUES (:contact_id,:tag_id,NOW(),:created_by)');
        $stmt->execute(['contact_id' => $contactId, 'tag_id' => $tagId, 'created_by' => $userId]);
    }

    public function removeTag(int $contactId, int $tagId): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM contact_tags WHERE contact_id=:contact_id AND tag_id=:tag_id');
        $stmt->execute(['contact_id' => $contactId, 'tag_id' => $tagId]);
    }
}
