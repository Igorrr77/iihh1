<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\EmailAutomationService;

final class MarketingRepository
{
    public function saveSegment(int $webinarId, string $leadToken, string $segment): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO lead_segments (webinar_id, lead_token, segment_name) VALUES (:webinar_id,:lead_token,:segment_name)
             ON DUPLICATE KEY UPDATE segment_name = VALUES(segment_name), updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute(['webinar_id' => $webinarId, 'lead_token' => $leadToken, 'segment_name' => $segment]);
    }

    public function enqueueEmail(string $leadToken, string $templateKey, string $payload): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO email_queue (lead_token, template_key, payload_json, status) VALUES (:lead_token,:template_key,:payload_json,:status)');
        $stmt->execute([
            'lead_token' => $leadToken,
            'template_key' => $templateKey,
            'payload_json' => $payload,
            'status' => 'queued',
        ]);
    }

    public function enqueueChannel(string $channel, string $leadToken, string $templateKey, array $payload): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO channel_queue (channel, lead_token, template_key, payload_json, status, next_retry_at)
             VALUES (:channel,:lead_token,:template_key,:payload_json,"queued", UTC_TIMESTAMP())'
        );
        $stmt->execute([
            'channel' => $channel,
            'lead_token' => $leadToken,
            'template_key' => $templateKey,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function processChannelQueue(string $channel, int $limit = 50): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id, lead_token, template_key, payload_json, attempts
             FROM channel_queue
             WHERE channel = :channel
               AND status IN ("queued", "failed")
               AND (next_retry_at IS NULL OR next_retry_at <= UTC_TIMESTAMP())
             ORDER BY id ASC LIMIT :lim'
        );
        $stmt->bindValue(':channel', $channel);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll() ?: [];

        $policy = (new EmailAutomationService())->channelRetryPolicy($channel);
        $maxAttempts = (int) ($policy['max_attempts'] ?? 3);
        $baseDelay = (int) ($policy['base_delay_sec'] ?? 300);

        $result = ['sent' => 0, 'retried' => 0, 'dlq' => 0];
        foreach ($items as $item) {
            $attempts = (int) ($item['attempts'] ?? 0) + 1;
            $payload = json_decode((string) ($item['payload_json'] ?? '{}'), true);
            $forceFail = is_array($payload) && (($payload['force_fail'] ?? false) === true);
            $ok = !$forceFail && $attempts <= 2;

            if ($ok) {
                $pdo->prepare('UPDATE channel_queue SET status = "sent", attempts = :attempts, last_error_code = NULL, last_error_reason = NULL, updated_at = UTC_TIMESTAMP() WHERE id = :id')
                    ->execute(['attempts' => $attempts, 'id' => (int) $item['id']]);
                $result['sent']++;
                continue;
            }

            $errorCode = $forceFail ? 'provider_rejected' : 'delivery_timeout';
            $errorReason = $forceFail ? 'Provider rejected payload' : 'Temporary delivery timeout';

            if ($attempts >= $maxAttempts) {
                $pdo->prepare('UPDATE channel_queue SET status = "dlq", attempts = :attempts, last_error_code = :error_code, last_error_reason = :error_reason, updated_at = UTC_TIMESTAMP() WHERE id = :id')
                    ->execute([
                        'attempts' => $attempts,
                        'error_code' => $errorCode,
                        'error_reason' => $errorReason,
                        'id' => (int) $item['id'],
                    ]);
                $result['dlq']++;
            } else {
                $delay = $baseDelay * max(1, $attempts);
                $pdo->prepare('UPDATE channel_queue SET status = "failed", attempts = :attempts, last_error_code = :error_code, last_error_reason = :error_reason, next_retry_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL :delay SECOND), updated_at = UTC_TIMESTAMP() WHERE id = :id')
                    ->execute([
                        'attempts' => $attempts,
                        'error_code' => $errorCode,
                        'error_reason' => $errorReason,
                        'delay' => $delay,
                        'id' => (int) $item['id'],
                    ]);
                $result['retried']++;
            }
        }

        return $result;
    }

    public function upsertMessengerCuid(string $leadToken, string $messenger, string $cuid): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO messenger_cuid_map (lead_token, messenger, cuid)
             VALUES (:lead_token,:messenger,:cuid)
             ON DUPLICATE KEY UPDATE cuid = VALUES(cuid)'
        );
        $stmt->execute(['lead_token' => $leadToken, 'messenger' => $messenger, 'cuid' => $cuid]);
    }

    public function routeCrm(string $provider, string $leadToken, array $payload): bool
    {
        $idempotency = hash('sha256', $provider . '|' . $leadToken . '|' . json_encode($payload));
        $pdo = Database::connection();

        $exists = $pdo->prepare('SELECT id FROM crm_dispatches WHERE idempotency_key = :k LIMIT 1');
        $exists->execute(['k' => $idempotency]);
        if (is_array($exists->fetch())) {
            return false;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO crm_dispatches (idempotency_key, provider, lead_token, payload_json, status, attempts, next_retry_at)
             VALUES (:k,:provider,:lead_token,:payload_json,"pending",0,UTC_TIMESTAMP())'
        );
        $stmt->execute([
            'k' => $idempotency,
            'provider' => $provider,
            'lead_token' => $leadToken,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return true;
    }

    public function processCrmQueue(string $provider, int $limit = 50): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id, payload_json, attempts
             FROM crm_dispatches
             WHERE provider = :provider
               AND status IN ("pending", "failed")
               AND (next_retry_at IS NULL OR next_retry_at <= UTC_TIMESTAMP())
             ORDER BY id ASC LIMIT :lim'
        );
        $stmt->bindValue(':provider', $provider);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll() ?: [];

        $result = ['sent' => 0, 'retried' => 0, 'dlq' => 0];
        foreach ($items as $item) {
            $attempts = (int) ($item['attempts'] ?? 0) + 1;
            $payload = json_decode((string) ($item['payload_json'] ?? '{}'), true);
            $forceFail = is_array($payload) && (($payload['force_fail'] ?? false) === true);
            $ok = !$forceFail && $attempts <= 2;

            if ($ok) {
                $pdo->prepare('UPDATE crm_dispatches SET status = "sent", attempts = :attempts, last_error = NULL, last_error_code = NULL, updated_at = UTC_TIMESTAMP() WHERE id = :id')
                    ->execute(['attempts' => $attempts, 'id' => (int) $item['id']]);
                $result['sent']++;
                continue;
            }

            if ($attempts >= 4) {
                $pdo->prepare('UPDATE crm_dispatches SET status = "dlq", attempts = :attempts, last_error = :err, last_error_code = :code, updated_at = UTC_TIMESTAMP() WHERE id = :id')
                    ->execute([
                        'attempts' => $attempts,
                        'err' => 'CRM provider rejected payload',
                        'code' => 'crm_rejected',
                        'id' => (int) $item['id'],
                    ]);
                $result['dlq']++;
            } else {
                $delay = 300 * $attempts;
                $pdo->prepare('UPDATE crm_dispatches SET status = "failed", attempts = :attempts, last_error = :err, last_error_code = :code, next_retry_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL :delay SECOND), updated_at = UTC_TIMESTAMP() WHERE id = :id')
                    ->execute([
                        'attempts' => $attempts,
                        'err' => 'Temporary CRM transport error',
                        'code' => 'transport_error',
                        'delay' => $delay,
                        'id' => (int) $item['id'],
                    ]);
                $result['retried']++;
            }
        }

        return $result;
    }

    public function queueDlqSummary(): array
    {
        $pdo = Database::connection();
        $channelDlq = $pdo->query('SELECT channel AS queue_key, COUNT(*) AS dlq_count FROM channel_queue WHERE status = "dlq" GROUP BY channel')->fetchAll() ?: [];
        $crmDlq = $pdo->query('SELECT provider AS queue_key, COUNT(*) AS dlq_count FROM crm_dispatches WHERE status = "dlq" GROUP BY provider')->fetchAll() ?: [];

        return [
            'channel_dlq' => $channelDlq,
            'crm_dlq' => $crmDlq,
        ];
    }

    public function queuedEmails(int $limit = 50): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, lead_token, template_key, status, created_at FROM email_queue ORDER BY id DESC LIMIT :lim');
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
