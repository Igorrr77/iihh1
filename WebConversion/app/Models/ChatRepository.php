<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ChatRepository
{
    public function addMessage(int $webinarId, string $leadToken, string $name, string $message, bool $isVisible = true, bool $isAdminReply = false): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO chat_messages (webinar_id, lead_token, author_name, message_text, is_visible, is_admin_reply) VALUES (:webinar_id,:lead_token,:author_name,:message_text,:is_visible,:is_admin_reply)'
        );
        $stmt->execute([
            'webinar_id' => $webinarId,
            'lead_token' => $leadToken,
            'author_name' => $name,
            'message_text' => $message,
            'is_visible' => $isVisible ? 1 : 0,
            'is_admin_reply' => $isAdminReply ? 1 : 0,
        ]);
    }

    public function listMessages(int $webinarId, ?string $leadToken = null, bool $individualMode = false, int $sinceId = 0): array
    {
        $pdo = Database::connection();
        if ($individualMode && $leadToken !== null) {
            $stmt = $pdo->prepare(
                'SELECT id, author_name, message_text, created_at
                 FROM chat_messages
                 WHERE webinar_id = :webinar_id
                   AND id > :since_id
                   AND is_visible = 1
                   AND (lead_token = :lead_token OR is_admin_reply = 1)
                 ORDER BY id ASC
                 LIMIT 200'
            );
            $stmt->execute(['webinar_id' => $webinarId, 'lead_token' => $leadToken, 'since_id' => $sinceId]);
        } else {
            $stmt = $pdo->prepare(
                'SELECT id, author_name, message_text, created_at
                 FROM chat_messages
                 WHERE webinar_id = :webinar_id
                   AND id > :since_id
                   AND is_visible = 1
                 ORDER BY id ASC
                 LIMIT 200'
            );
            $stmt->execute(['webinar_id' => $webinarId, 'since_id' => $sinceId]);
        }

        return $stmt->fetchAll() ?: [];
    }

    public function hideMessage(int $messageId): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE chat_messages SET is_visible = 0 WHERE id = :id');
        $stmt->execute(['id' => $messageId]);
    }

    public function banLead(int $webinarId, string $leadToken, string $reason): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO chat_bans (webinar_id, lead_token, reason) VALUES (:webinar_id,:lead_token,:reason)');
        $stmt->execute(['webinar_id' => $webinarId, 'lead_token' => $leadToken, 'reason' => $reason]);
    }

    public function muteLead(int $webinarId, string $leadToken, int $durationSec, string $reason): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO chat_mutes (webinar_id, lead_token, muted_until, reason)
             VALUES (:webinar_id,:lead_token, DATE_ADD(UTC_TIMESTAMP(), INTERVAL :duration SECOND), :reason)
             ON DUPLICATE KEY UPDATE muted_until = DATE_ADD(UTC_TIMESTAMP(), INTERVAL :duration2 SECOND), reason = :reason2'
        );
        $stmt->bindValue(':webinar_id', $webinarId, \PDO::PARAM_INT);
        $stmt->bindValue(':lead_token', $leadToken);
        $stmt->bindValue(':duration', max(1, $durationSec), \PDO::PARAM_INT);
        $stmt->bindValue(':reason', $reason);
        $stmt->bindValue(':duration2', max(1, $durationSec), \PDO::PARAM_INT);
        $stmt->bindValue(':reason2', $reason);
        $stmt->execute();
    }

    public function isMuted(int $webinarId, string $leadToken): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id FROM chat_mutes WHERE webinar_id = :webinar_id AND lead_token = :lead_token AND muted_until > UTC_TIMESTAMP() LIMIT 1');
        $stmt->execute(['webinar_id' => $webinarId, 'lead_token' => $leadToken]);
        return is_array($stmt->fetch());
    }

    public function recordMetric(int $webinarId, string $transport, int $latencyMs, bool $isError): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO chat_delivery_metrics (webinar_id, transport, latency_ms, is_error) VALUES (:webinar_id,:transport,:latency_ms,:is_error)'
        );
        $stmt->execute([
            'webinar_id' => $webinarId,
            'transport' => $transport,
            'latency_ms' => max(0, $latencyMs),
            'is_error' => $isError ? 1 : 0,
        ]);
    }

    public function metricP95Ms(int $webinarId): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT latency_ms FROM chat_delivery_metrics WHERE webinar_id = :webinar_id ORDER BY latency_ms ASC');
        $stmt->execute(['webinar_id' => $webinarId]);
        $values = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
        if ($values === []) {
            return 0;
        }

        $index = (int) ceil(count($values) * 0.95) - 1;
        $index = max(0, min($index, count($values) - 1));
        return $values[$index];
    }

    public function errorCount(int $webinarId): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM chat_delivery_metrics WHERE webinar_id = :webinar_id AND is_error = 1');
        $stmt->execute(['webinar_id' => $webinarId]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function isBanned(int $webinarId, string $leadToken): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id FROM chat_bans WHERE webinar_id = :webinar_id AND lead_token = :lead_token LIMIT 1');
        $stmt->execute(['webinar_id' => $webinarId, 'lead_token' => $leadToken]);
        return is_array($stmt->fetch());
    }
}
