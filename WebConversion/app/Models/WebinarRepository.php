<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class WebinarRepository
{
    public function create(string $externalId, string $title, string $format, string $timezone, string $accessMode): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO webinars (external_id, title, format, timezone, access_mode) VALUES (:external_id, :title, :format, :timezone, :access_mode)'
        );
        $stmt->execute([
            'external_id' => $externalId,
            'title' => $title,
            'format' => $format,
            'timezone' => $timezone,
            'access_mode' => $accessMode,
        ]);

        return [
            'id' => (int) $pdo->lastInsertId(),
            'external_id' => $externalId,
            'title' => $title,
            'format' => $format,
            'timezone' => $timezone,
            'access_mode' => $accessMode,
        ];
    }

    public function findByExternalId(string $externalId): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM webinars WHERE external_id = :external_id LIMIT 1');
        $stmt->execute(['external_id' => $externalId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }
}
