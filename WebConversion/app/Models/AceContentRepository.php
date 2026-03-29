<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AceContentRepository
{
    public function save(int $webinarId, string $contentType, string $contentText): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO ace_contents (webinar_id, content_type, content_text) VALUES (:webinar_id,:content_type,:content_text)'
        );
        $stmt->execute([
            'webinar_id' => $webinarId,
            'content_type' => $contentType,
            'content_text' => $contentText,
        ]);
    }

    public function listByWebinar(int $webinarId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT content_type, content_text, created_at FROM ace_contents WHERE webinar_id = :webinar_id ORDER BY id DESC LIMIT 50');
        $stmt->execute(['webinar_id' => $webinarId]);
        return $stmt->fetchAll() ?: [];
    }
}
