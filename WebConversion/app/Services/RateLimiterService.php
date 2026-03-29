<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class RateLimiterService
{
    public function hit(string $scope, string $ip, int $limitPerMinute): bool
    {
        $pdo = Database::connection();
        $minuteKey = gmdate('Y-m-d H:i:00');

        $select = $pdo->prepare(
            'SELECT id, hits FROM rate_limits WHERE scope = :scope AND ip = :ip AND minute_key = :minute_key LIMIT 1'
        );
        $select->execute([
            'scope' => $scope,
            'ip' => $ip,
            'minute_key' => $minuteKey,
        ]);
        $existing = $select->fetch();

        if (is_array($existing)) {
            $newHits = ((int) $existing['hits']) + 1;
            $update = $pdo->prepare('UPDATE rate_limits SET hits = :hits WHERE id = :id');
            $update->execute(['hits' => $newHits, 'id' => (int) $existing['id']]);
            return $newHits <= $limitPerMinute;
        }

        $insert = $pdo->prepare(
            'INSERT INTO rate_limits (scope, ip, minute_key, hits) VALUES (:scope, :ip, :minute_key, 1)'
        );
        $insert->execute([
            'scope' => $scope,
            'ip' => $ip,
            'minute_key' => $minuteKey,
        ]);

        return true;
    }
}
