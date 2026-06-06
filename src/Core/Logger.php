<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Logger
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    private function log(string $level, string $message, array $context): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO run_logs(level, message, context_json, created_at) VALUES (:level, :message, :ctx, UTC_TIMESTAMP())');
        $stmt->execute([
            ':level' => $level,
            ':message' => $message,
            ':ctx' => json_encode($context, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
