<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class RoomStateRepository
{
    public function upsert(int $webinarId, string $state, ?string $message): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO webinar_room_states (webinar_id, room_state, message) VALUES (:webinar_id,:room_state,:message)
             ON DUPLICATE KEY UPDATE room_state = VALUES(room_state), message = VALUES(message), updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute(['webinar_id' => $webinarId, 'room_state' => $state, 'message' => $message]);
    }

    public function get(int $webinarId): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT room_state, message, updated_at FROM webinar_room_states WHERE webinar_id = :webinar_id LIMIT 1');
        $stmt->execute(['webinar_id' => $webinarId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }
}
