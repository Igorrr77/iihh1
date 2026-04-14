<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\RoomStateRepository;
use App\Models\ScenarioRepository;
use App\Models\TimelineRepository;
use App\Models\WebinarRepository;
use App\Services\EmbedSdkContractService;
use App\Services\LiveToAutoConversionService;
use App\Services\RbacAuthService;
use App\Services\StreamEmbedTokenService;
use App\Services\VideoProviderAdapter;

final class StreamController
{
    public function resolvePlayback(): void
    {
        $payload = $this->readJsonBody();
        $provider = (string) ($payload['provider'] ?? 'youtube');
        $externalId = (string) ($payload['external_id'] ?? '');

        if ($externalId === '') {
            Response::json(['error' => 'external_id обязателен'], 422);
            return;
        }

        Response::json((new VideoProviderAdapter())->resolvePlayback($provider, $externalId));
    }

    public function createEmbedToken(): void
    {
        $payload = $this->readJsonBody();
        $webinarId = (string) ($payload['webinar_id'] ?? '');
        $origin = (string) ($payload['origin'] ?? '');
        $ttl = (int) ($payload['ttl_sec'] ?? 900);

        if ($webinarId === '' || $origin === '') {
            Response::json(['error' => 'webinar_id и origin обязательны'], 422);
            return;
        }

        $token = (new StreamEmbedTokenService())->createSignedToken($webinarId, $origin, $ttl);
        if ($token === '') {
            Response::json(['error' => 'EMBED_TOKEN_SECRET не настроен'], 500);
            return;
        }

        Response::json(['embed_token' => $token]);
    }


    public function sdkContract(): void
    {
        Response::json((new EmbedSdkContractService())->contract());
    }

    public function convertLiveToAuto(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $payload = $this->readJsonBody();
        $webinarExternalId = (string) ($payload['webinar_id'] ?? '');

        $webinar = (new WebinarRepository())->findByExternalId($webinarExternalId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $eventsRaw = (new TimelineRepository())->listEvents((int) $webinar['id']);
        $events = array_map(static fn (array $e): array => [
            'second_from_start' => (int) ($e['at'] ?? 0),
            'event_type' => (string) ($e['type'] ?? 'chat'),
            'payload' => $e['payload'] ?? null,
        ], $eventsRaw);

        $converted = (new LiveToAutoConversionService())->convert($events);
        (new ScenarioRepository())->save((int) $webinar['id'], ['mode' => 'auto', 'events' => $converted]);

        Response::json(['ok' => true, 'events_converted' => count($converted)]);
    }

    public function setRoomState(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin', 'moderator']);
        $payload = $this->readJsonBody();
        $webinarId = (string) ($payload['webinar_id'] ?? '');
        $state = (string) ($payload['state'] ?? 'waiting');
        $message = isset($payload['message']) ? (string) $payload['message'] : null;

        $webinar = (new WebinarRepository())->findByExternalId($webinarId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        (new RoomStateRepository())->upsert((int) $webinar['id'], $state, $message);
        Response::json(['ok' => true]);
    }

    public function getRoomState(): void
    {
        $webinarId = (string) ($_GET['webinar_id'] ?? '');
        $webinar = (new WebinarRepository())->findByExternalId($webinarId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $state = (new RoomStateRepository())->get((int) $webinar['id']);
        Response::json(['room_state' => $state]);
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
