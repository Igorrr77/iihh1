<?php

declare(strict_types=1);

namespace App\Repositories;

final class GuardrailRepository extends BaseRepository
{
    public function takeIdempotency(int $accountId, string $key, string $requestHash): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM api_idempotency_keys WHERE account_id=:account_id AND idempotency_key=:idempotency_key LIMIT 1');
        $stmt->execute(['account_id' => $accountId, 'idempotency_key' => $key]);
        $row = $stmt->fetch();

        if ($row) {
            if ($row['request_hash'] !== $requestHash) {
                return ['status' => 'conflict'];
            }
            return ['status' => 'replay', 'response_json' => $row['response_json'] ? json_decode((string)$row['response_json'], true) : null];
        }

        $insert = $this->pdo()->prepare('INSERT INTO api_idempotency_keys (account_id, idempotency_key, request_hash, created_at, expires_at) VALUES (:account_id,:idempotency_key,:request_hash,NOW(),DATE_ADD(NOW(), INTERVAL 24 HOUR))');
        $insert->execute(['account_id' => $accountId, 'idempotency_key' => $key, 'request_hash' => $requestHash]);

        return ['status' => 'new'];
    }

    public function saveIdempotencyResponse(int $accountId, string $key, array $response): void
    {
        $stmt = $this->pdo()->prepare('UPDATE api_idempotency_keys SET response_json=:response_json WHERE account_id=:account_id AND idempotency_key=:idempotency_key');
        $stmt->execute(['account_id' => $accountId, 'idempotency_key' => $key, 'response_json' => json_encode($response, JSON_UNESCAPED_UNICODE)]);
    }

    public function hitRateLimit(string $scope, string $hitKey, int $limit, int $windowMinutes): bool
    {
        $windowStart = date('Y-m-d H:i:00', (int)(floor(time() / ($windowMinutes * 60)) * ($windowMinutes * 60)));
        $stmt = $this->pdo()->prepare('INSERT INTO rate_limit_hits (scope, hit_key, window_start, hits, updated_at) VALUES (:scope,:hit_key,:window_start,1,NOW()) ON DUPLICATE KEY UPDATE hits = hits + 1, updated_at=NOW()');
        $stmt->execute(['scope' => $scope, 'hit_key' => $hitKey, 'window_start' => $windowStart]);

        $check = $this->pdo()->prepare('SELECT hits FROM rate_limit_hits WHERE scope=:scope AND hit_key=:hit_key AND window_start=:window_start LIMIT 1');
        $check->execute(['scope' => $scope, 'hit_key' => $hitKey, 'window_start' => $windowStart]);
        return ((int)$check->fetchColumn()) > $limit;
    }

    public function audit(array $payload): void
    {
        $stmt = $this->pdo()->prepare('INSERT INTO audit_logs (account_id, project_id, user_id, entity_type, entity_id, action, before_json, after_json, ip_address, user_agent, created_at) VALUES (:account_id,:project_id,:user_id,:entity_type,:entity_id,:action,:before_json,:after_json,:ip_address,:user_agent,NOW())');
        $stmt->execute([
            'account_id' => $payload['account_id'] ?? null,
            'project_id' => $payload['project_id'] ?? null,
            'user_id' => $payload['user_id'] ?? null,
            'entity_type' => $payload['entity_type'] ?? 'api',
            'entity_id' => $payload['entity_id'] ?? null,
            'action' => $payload['action'] ?? 'unknown',
            'before_json' => json_encode($payload['before'] ?? null, JSON_UNESCAPED_UNICODE),
            'after_json' => json_encode($payload['after'] ?? null, JSON_UNESCAPED_UNICODE),
            'ip_address' => $payload['ip_address'] ?? null,
            'user_agent' => $payload['user_agent'] ?? null,
        ]);
    }
}
