<?php

declare(strict_types=1);

namespace Commentor;

use PDO;

final class Logger
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function info(string $message, array $payload = []): void
    {
        $this->write('info', $message, $payload);
    }

    public function warning(string $message, array $payload = []): void
    {
        $this->write('warning', $message, $payload);
    }

    public function error(string $message, array $payload = []): void
    {
        $this->write('error', $message, $payload);
    }

    private function write(string $level, string $message, array $payload): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO logs(level, message, payload_json) VALUES(:level,:message,:payload)');
        $stmt->execute([
            ':level' => $level,
            ':message' => $message,
            ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
