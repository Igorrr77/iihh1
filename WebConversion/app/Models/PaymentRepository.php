<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDOException;

final class PaymentRepository
{
    public function create(int $webinarId, string $leadToken, int $amountCents, string $currency, string $provider, string $status): string
    {
        $paymentId = 'pay_' . bin2hex(random_bytes(8));
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO payments (payment_id, webinar_id, lead_token, amount_cents, currency, provider, status, retry_count) VALUES (:payment_id,:webinar_id,:lead_token,:amount_cents,:currency,:provider,:status,0)'
        );
        $stmt->execute([
            'payment_id' => $paymentId,
            'webinar_id' => $webinarId,
            'lead_token' => $leadToken,
            'amount_cents' => $amountCents,
            'currency' => strtoupper($currency),
            'provider' => $provider,
            'status' => $status,
        ]);

        return $paymentId;
    }

    public function findByPaymentId(string $paymentId): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE payment_id = :payment_id LIMIT 1');
        $stmt->execute(['payment_id' => $paymentId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function recordWebhookEventIfNew(string $provider, string $eventId, string $paymentId, string $payload): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO payment_webhook_events (provider, provider_event_id, payment_id, payload_json) VALUES (:provider,:event_id,:payment_id,:payload_json)'
        );

        try {
            $stmt->execute([
                'provider' => $provider,
                'event_id' => $eventId,
                'payment_id' => $paymentId,
                'payload_json' => $payload,
            ]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    public function updateStatus(string $paymentId, string $status, ?string $providerPayload): void
    {
        $allowed = ['pending', 'paid', 'failed', 'refunded', 'canceled'];
        if (!in_array($status, $allowed, true)) {
            $status = 'failed';
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE payments SET status = :status, provider_payload = :provider_payload, updated_at = CURRENT_TIMESTAMP WHERE payment_id = :payment_id');
        $stmt->execute([
            'status' => $status,
            'provider_payload' => $providerPayload,
            'payment_id' => $paymentId,
        ]);
    }

    public function incrementRetry(string $paymentId, string $reason): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE payments
             SET retry_count = retry_count + 1,
                 last_error_code = :reason,
                 next_retry_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 5 MINUTE),
                 updated_at = CURRENT_TIMESTAMP
             WHERE payment_id = :payment_id'
        );
        $stmt->execute([
            'reason' => $reason,
            'payment_id' => $paymentId,
        ]);

        $row = $this->findByPaymentId($paymentId);
        return (int) ($row['retry_count'] ?? 0);
    }

    public function listRetryCandidates(int $limit = 100): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT payment_id, provider, status, retry_count, last_error_code, next_retry_at, updated_at
             FROM payments
             WHERE status = "failed" AND retry_count < 3
             ORDER BY updated_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', max(1, min($limit, 500)), \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function listByWebinar(int $webinarId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT payment_id, amount_cents, currency, provider, status, retry_count, last_error_code, next_retry_at, created_at, updated_at FROM payments WHERE webinar_id = :webinar_id ORDER BY id DESC LIMIT 500');
        $stmt->execute(['webinar_id' => $webinarId]);
        return $stmt->fetchAll() ?: [];
    }

    public function reconciliationSummary(int $webinarId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT status, COUNT(*) AS cnt, COALESCE(SUM(amount_cents),0) AS amount_sum FROM payments WHERE webinar_id = :webinar_id GROUP BY status');
        $stmt->execute(['webinar_id' => $webinarId]);
        return $stmt->fetchAll() ?: [];
    }

    public function providerBreakdown(int $webinarId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT provider, COUNT(*) AS cnt, COALESCE(SUM(amount_cents),0) AS amount_sum FROM payments WHERE webinar_id = :webinar_id GROUP BY provider ORDER BY cnt DESC');
        $stmt->execute(['webinar_id' => $webinarId]);
        return $stmt->fetchAll() ?: [];
    }
}
