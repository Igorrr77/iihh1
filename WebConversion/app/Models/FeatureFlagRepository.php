<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class FeatureFlagRepository
{
    public function upsert(string $flagKey, bool $enabled): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO feature_flags (flag_key, is_enabled) VALUES (:flag_key,:is_enabled)
             ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled), updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute(['flag_key' => $flagKey, 'is_enabled' => $enabled ? 1 : 0]);
    }

    public function all(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT flag_key, is_enabled, updated_at FROM feature_flags ORDER BY flag_key ASC');
        return $stmt->fetchAll() ?: [];
    }
}
