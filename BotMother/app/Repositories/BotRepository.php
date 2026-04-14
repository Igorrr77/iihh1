<?php

declare(strict_types=1);

namespace App\Repositories;

final class BotRepository extends BaseRepository
{
    public function create(array $data): array
    {
        $stmt = $this->pdo()->prepare('INSERT INTO bots (account_id, project_id, name, token_encrypted, webhook_secret, webhook_status, status, created_by, created_at, updated_at) VALUES (:account_id,:project_id,:name,:token_encrypted,:webhook_secret,:webhook_status,:status,:created_by,NOW(),NOW())');
        $stmt->execute([
            'account_id' => $data['account_id'],
            'project_id' => $data['project_id'],
            'name' => $data['name'],
            'token_encrypted' => $data['token_encrypted'],
            'webhook_secret' => $data['webhook_secret'],
            'webhook_status' => 'not_set',
            'status' => 'active',
            'created_by' => $data['created_by'],
        ]);

        $id = (int)$this->pdo()->lastInsertId();
        $stmt = $this->pdo()->prepare('SELECT * FROM bots WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM bots WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function updateWebhook(int $id, string $url, string $status): void
    {
        $stmt = $this->pdo()->prepare('UPDATE bots SET webhook_url=:url, webhook_status=:status, last_webhook_at=NOW(), updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $id, 'url' => $url, 'status' => $status]);
    }
}
