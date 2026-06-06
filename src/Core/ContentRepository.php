<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class ContentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(int $sourceId, string $source, array $item): void
    {
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO content_items(source_id, source, content_type, external_id, author, title, body, popularity_json, media_json, published_at, fetched_at, raw_json)
        VALUES(:source_id, :source, :content_type, :external_id, :author, :title, :body, :popularity_json, :media_json, :published_at, UTC_TIMESTAMP(), :raw_json)');

        $stmt->execute([
            ':source_id' => $sourceId,
            ':source' => $source,
            ':content_type' => (string) ($item['content_type'] ?? 'post'),
            ':external_id' => (string) ($item['external_id'] ?? ''),
            ':author' => (string) ($item['author'] ?? ''),
            ':title' => (string) ($item['title'] ?? ''),
            ':body' => (string) ($item['body'] ?? ''),
            ':popularity_json' => json_encode($item['popularity'] ?? [], JSON_UNESCAPED_UNICODE),
            ':media_json' => json_encode($item['media'] ?? [], JSON_UNESCAPED_UNICODE),
            ':published_at' => (string) ($item['published_at'] ?? gmdate('Y-m-d H:i:s')),
            ':raw_json' => json_encode($item['raw'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
    }
}
