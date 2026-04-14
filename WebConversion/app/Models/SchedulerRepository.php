<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class SchedulerRepository
{
    public function createSession(int $webinarId, string $mode, string $timezone, string $startAt): string
    {
        $sessionId = 'sess_' . bin2hex(random_bytes(8));
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO webinar_sessions (session_id, webinar_id, mode, timezone, start_at) VALUES (:session_id,:webinar_id,:mode,:timezone,:start_at)'
        );
        $stmt->execute([
            'session_id' => $sessionId,
            'webinar_id' => $webinarId,
            'mode' => $mode,
            'timezone' => $timezone,
            'start_at' => $startAt,
        ]);

        return $sessionId;
    }

    public function nextForWebinar(int $webinarId): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT session_id, mode, timezone, start_at FROM webinar_sessions WHERE webinar_id = :webinar_id AND start_at >= UTC_TIMESTAMP() ORDER BY start_at ASC LIMIT 1'
        );
        $stmt->execute(['webinar_id' => $webinarId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }
}
