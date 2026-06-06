<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class ExportService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function streamCsv(array $filters = []): void
    {
        $where = [];
        $params = [];

        if (($filters['provider'] ?? '') !== '') {
            $where[] = 'source = :provider';
            $params[':provider'] = $filters['provider'];
        }
        if (($filters['content_type'] ?? '') !== '') {
            $where[] = 'content_type = :content_type';
            $params[':content_type'] = $filters['content_type'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(title LIKE :q OR body LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        $sql = 'SELECT id, source, content_type, external_id, author, title, body, popularity_json, published_at, fetched_at FROM content_items';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY id DESC LIMIT 5000';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="export_' . gmdate('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'wb');
        fputcsv($out, ['id', 'source', 'content_type', 'external_id', 'author', 'title', 'body', 'likes', 'comments', 'views', 'published_at', 'fetched_at']);

        while ($row = $stmt->fetch()) {
            $pop = json_decode((string) $row['popularity_json'], true) ?: [];
            fputcsv($out, [
                $row['id'],
                $row['source'],
                $row['content_type'],
                $row['external_id'],
                $row['author'],
                $row['title'],
                $row['body'],
                (string) ($pop['likes'] ?? 0),
                (string) ($pop['comments'] ?? 0),
                (string) ($pop['views'] ?? 0),
                $row['published_at'],
                $row['fetched_at'],
            ]);
        }

        fclose($out);
    }
}
