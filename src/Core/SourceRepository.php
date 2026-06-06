<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class SourceRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO sources(provider, account_handle, mode, query_text, content_types_json, filters_json, is_active, created_at, updated_at)
        VALUES(:provider, :account_handle, :mode, :query_text, :content_types_json, :filters_json, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())');
        $stmt->execute([
            ':provider' => $data['provider'],
            ':account_handle' => $data['account_handle'],
            ':mode' => $data['mode'],
            ':query_text' => $data['query_text'],
            ':content_types_json' => json_encode($data['content_types'], JSON_UNESCAPED_UNICODE),
            ':filters_json' => json_encode($data['filters'], JSON_UNESCAPED_UNICODE),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM sources ORDER BY id DESC LIMIT 100')->fetchAll();
    }

    public function byId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sources WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $source = $stmt->fetch();

        if (!$source) {
            return null;
        }

        $source['content_types'] = json_decode((string) $source['content_types_json'], true) ?: [];
        $source['filters'] = json_decode((string) $source['filters_json'], true) ?: [];
        return $source;
    }
}
