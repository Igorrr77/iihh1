<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\OfferRepository;
use App\Models\WebinarRepository;
use App\Services\OfferService;
use App\Services\RbacAuthService;

final class OfferController
{
    public function create(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin', 'sales']);
        $payload = $this->readJsonBody();
        $webinarId = (string) ($payload['webinar_id'] ?? '');
        $title = (string) ($payload['title'] ?? 'Оффер');
        $description = (string) ($payload['description'] ?? '');
        $ttlSec = (int) ($payload['ttl_sec'] ?? 900);
        $ctaUrl = (string) ($payload['cta_url'] ?? '');

        $webinar = (new WebinarRepository())->findByExternalId($webinarId);
        if ($webinar === null || $ctaUrl === '') {
            Response::json(['error' => 'Неверные входные данные'], 422);
            return;
        }

        $id = (new OfferRepository())->create((int) $webinar['id'], $title, $description, $ttlSec, $ctaUrl);
        Response::json(['offer_id' => $id], 201);
    }

    public function activate(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin', 'sales']);
        $payload = $this->readJsonBody();
        $offerId = (int) ($payload['offer_id'] ?? 0);
        if ($offerId <= 0) {
            Response::json(['error' => 'offer_id обязателен'], 422);
            return;
        }

        $repo = new OfferRepository();
        $repo->activate($offerId);
        Response::json(['ok' => true]);
    }

    public function active(): void
    {
        $webinarId = (string) ($_GET['webinar_id'] ?? '');
        $webinar = (new WebinarRepository())->findByExternalId($webinarId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $offers = (new OfferRepository())->activeByWebinar((int) $webinar['id']);
        $service = new OfferService();
        foreach ($offers as &$offer) {
            if (!empty($offer['activated_at'])) {
                $offer['expires_at'] = $service->expiresAt((string) $offer['activated_at'], (int) $offer['ttl_sec']);
            }
        }
        unset($offer);

        Response::json(['offers' => $offers]);
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
