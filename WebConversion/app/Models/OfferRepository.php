<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class OfferRepository
{
    public function create(int $webinarId, string $title, string $description, int $ttlSec, string $ctaUrl): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO offer_cards (webinar_id, title, description, ttl_sec, cta_url) VALUES (:webinar_id,:title,:description,:ttl_sec,:cta_url)'
        );
        $stmt->execute([
            'webinar_id' => $webinarId,
            'title' => $title,
            'description' => $description,
            'ttl_sec' => $ttlSec,
            'cta_url' => $ctaUrl,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function activate(int $offerId): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE offer_cards SET activated_at = UTC_TIMESTAMP() WHERE id = :id');
        $stmt->execute(['id' => $offerId]);
    }

    public function activeByWebinar(int $webinarId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id, title, description, ttl_sec, cta_url, activated_at FROM offer_cards WHERE webinar_id = :webinar_id AND activated_at IS NOT NULL ORDER BY activated_at DESC LIMIT 20'
        );
        $stmt->execute(['webinar_id' => $webinarId]);
        return $stmt->fetchAll() ?: [];
    }
}
