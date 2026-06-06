<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class ScheduleRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $sourceId, int $intervalMinutes, string $timezone = 'UTC'): void
    {
        $nextRun = gmdate('Y-m-d H:i:s', time() + max(1, $intervalMinutes) * 60);
        $stmt = $this->pdo->prepare('INSERT INTO schedules(source_id, cron_expr, timezone, next_run_at, is_active, created_at, updated_at)
        VALUES(:source_id, :cron_expr, :timezone, :next_run_at, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())');
        $stmt->execute([
            ':source_id' => $sourceId,
            ':cron_expr' => 'every_' . max(1, $intervalMinutes) . '_minutes',
            ':timezone' => $timezone,
            ':next_run_at' => $nextRun,
        ]);
    }

    public function due(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM schedules WHERE is_active = 1 AND next_run_at <= UTC_TIMESTAMP() ORDER BY id ASC LIMIT 50');
        return $stmt->fetchAll();
    }

    public function reschedule(array $schedule): void
    {
        $expr = (string) $schedule['cron_expr'];
        $minutes = 5;
        if (preg_match('/every_(\d+)_minutes/', $expr, $m)) {
            $minutes = max(1, (int) $m[1]);
        }

        $nextRun = gmdate('Y-m-d H:i:s', time() + $minutes * 60);
        $stmt = $this->pdo->prepare('UPDATE schedules SET next_run_at = :next_run, updated_at = UTC_TIMESTAMP() WHERE id = :id');
        $stmt->execute([':next_run' => $nextRun, ':id' => $schedule['id']]);
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT s.*, src.provider, src.account_handle FROM schedules s JOIN sources src ON src.id = s.source_id ORDER BY s.id DESC LIMIT 100')->fetchAll();
    }
}
