<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Queue
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function push(string $type, array $payload, int $delaySeconds = 0, int $maxAttempts = 5): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $available = gmdate('Y-m-d H:i:s', time() + $delaySeconds);

        $stmt = $this->pdo->prepare('INSERT INTO jobs(type, payload, status, attempts, max_attempts, available_at, created_at, updated_at) VALUES(:type, :payload, "queued", 0, :max_attempts, :available_at, :created_at, :updated_at)');
        $stmt->execute([
            ':type' => $type,
            ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ':max_attempts' => $maxAttempts,
            ':available_at' => $available,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }

    public function pop(): ?array
    {
        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare('SELECT * FROM jobs WHERE status = "queued" AND available_at <= UTC_TIMESTAMP() ORDER BY id ASC LIMIT 1 FOR UPDATE');
        $stmt->execute();
        $job = $stmt->fetch();

        if (!$job) {
            $this->pdo->commit();
            return null;
        }

        $upd = $this->pdo->prepare('UPDATE jobs SET status = "running", updated_at = UTC_TIMESTAMP() WHERE id = :id');
        $upd->execute([':id' => $job['id']]);
        $this->pdo->commit();

        $job['payload'] = json_decode((string) $job['payload'], true) ?: [];
        return $job;
    }

    public function done(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE jobs SET status = "done", updated_at = UTC_TIMESTAMP() WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function fail(array $job, string $error): void
    {
        $attempts = (int) $job['attempts'] + 1;
        $maxAttempts = (int) $job['max_attempts'];

        if ($attempts >= $maxAttempts) {
            $stmt = $this->pdo->prepare('UPDATE jobs SET status = "failed", attempts = :attempts, last_error = :error, updated_at = UTC_TIMESTAMP() WHERE id = :id');
            $stmt->execute([':attempts' => $attempts, ':error' => $error, ':id' => $job['id']]);
            return;
        }

        $delay = (int) min(3600, pow(2, $attempts) * 10);
        $available = gmdate('Y-m-d H:i:s', time() + $delay);
        $stmt = $this->pdo->prepare('UPDATE jobs SET status = "queued", attempts = :attempts, last_error = :error, available_at = :available, updated_at = UTC_TIMESTAMP() WHERE id = :id');
        $stmt->execute([
            ':attempts' => $attempts,
            ':error' => $error,
            ':available' => $available,
            ':id' => $job['id'],
        ]);
    }
}
