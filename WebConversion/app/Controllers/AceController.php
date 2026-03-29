<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\AceContentRepository;
use App\Models\WebinarRepository;
use App\Services\AceContentService;
use App\Services\RbacAuthService;

final class AceController
{
    public function generate(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin', 'moderator']);
        $payload = $this->readJsonBody();
        $webinarId = (string) ($payload['webinar_id'] ?? '');
        $transcript = (string) ($payload['transcript'] ?? '');

        $webinar = (new WebinarRepository())->findByExternalId($webinarId);
        if ($webinar === null || $transcript === '') {
            Response::json(['error' => 'Неверные входные данные'], 422);
            return;
        }

        $pack = (new AceContentService())->generatePack($transcript);
        $repo = new AceContentRepository();
        foreach ($pack as $type => $text) {
            $repo->save((int) $webinar['id'], $type, $text);
        }

        Response::json(['generated' => $pack]);
    }

    public function qualityBenchmark(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin', 'moderator']);
        $webinarId = (string) ($_GET['webinar_id'] ?? '');
        $webinar = (new WebinarRepository())->findByExternalId($webinarId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $items = (new AceContentRepository())->listByWebinar((int) $webinar['id']);
        $benchmark = (new AceContentService())->qualityBenchmark($items);
        Response::json($benchmark);
    }

    public function list(): void
    {
        $webinarId = (string) ($_GET['webinar_id'] ?? '');
        $webinar = (new WebinarRepository())->findByExternalId($webinarId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $items = (new AceContentRepository())->listByWebinar((int) $webinar['id']);
        Response::json(['items' => $items]);
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
