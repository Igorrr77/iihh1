<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AuditLogRepository
{
    public function write(string $actor, string $action, array $meta = []): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (actor, action, meta_json) VALUES (:actor, :action, :meta_json)'
        );
        $stmt->execute([
            'actor' => $actor,
            'action' => $action,
            'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
