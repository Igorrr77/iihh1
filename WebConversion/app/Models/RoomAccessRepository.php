<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class RoomAccessRepository
{
    public function registerLead(int $webinarId, string $name, ?string $email, ?string $phone): string
    {
        $token = bin2hex(random_bytes(16));
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'INSERT INTO webinar_registrations (webinar_id, name, email, phone, access_token) VALUES (:webinar_id, :name, :email, :phone, :access_token)'
        );
        $stmt->execute([
            'webinar_id' => $webinarId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'access_token' => $token,
        ]);

        return $token;
    }

    public function logAttendance(int $webinarId, string $token, string $eventType): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO attendance_events (webinar_id, access_token, event_type, created_at) VALUES (:webinar_id, :access_token, :event_type, NOW())'
        );
        $stmt->execute([
            'webinar_id' => $webinarId,
            'access_token' => $token,
            'event_type' => $eventType,
        ]);
    }
}
