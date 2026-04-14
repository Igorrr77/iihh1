<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\MarketingRepository;
use App\Models\WebinarRepository;
use App\Services\EmailAutomationService;
use App\Services\RbacAuthService;
use App\Services\SegmentService;

final class MarketingController
{
    public function computeSegment(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin', 'sales']);
        $payload = $this->readJsonBody();

        $webinarExternalId = (string) ($payload['webinar_id'] ?? '');
        $leadToken = (string) ($payload['lead_token'] ?? '');
        $joined = (bool) ($payload['joined'] ?? false);
        $reachedOffer = (bool) ($payload['reached_offer'] ?? false);
        $purchased = (bool) ($payload['purchased'] ?? false);
        $registered = (bool) ($payload['registered'] ?? true);
        $watchSeconds = (int) ($payload['watch_seconds'] ?? 0);

        $webinar = (new WebinarRepository())->findByExternalId($webinarExternalId);
        if ($webinar === null || $leadToken === '') {
            Response::json(['error' => 'Неверные входные данные'], 422);
            return;
        }

        $segment = (new SegmentService())->detectSegment($joined, $reachedOffer, $purchased, $registered, $watchSeconds);
        (new MarketingRepository())->saveSegment((int) $webinar['id'], $leadToken, $segment);

        Response::json(['segment' => $segment]);
    }

    public function enqueueEmailCadence(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin', 'sales']);
        $payload = $this->readJsonBody();
        $leadToken = (string) ($payload['lead_token'] ?? '');
        $segment = (string) ($payload['segment'] ?? '');

        if ($leadToken === '' || $segment === '') {
            Response::json(['error' => 'lead_token и segment обязательны'], 422);
            return;
        }

        $automation = new EmailAutomationService();
        $repo = new MarketingRepository();

        foreach ($automation->orchestrationBySegment($segment) as $channel => $template) {
            if ($channel === 'email') {
                $repo->enqueueEmail($leadToken, $template, json_encode(['segment' => $segment], JSON_UNESCAPED_UNICODE));
            }
            $repo->enqueueChannel($channel, $leadToken, $template, ['segment' => $segment]);
        }

        Response::json(['queued' => true]);
    }

    public function processChannelQueue(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $channel = (string) ($_GET['channel'] ?? 'email');
        $result = (new MarketingRepository())->processChannelQueue($channel, 100);
        Response::json(['channel' => $channel, 'result' => $result]);
    }

    public function trackMessengerCuid(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin', 'sales']);
        $payload = $this->readJsonBody();
        $leadToken = (string) ($payload['lead_token'] ?? '');
        $messenger = (string) ($payload['messenger'] ?? 'telegram');
        $cuid = (string) ($payload['cuid'] ?? '');

        if ($leadToken === '' || $cuid === '') {
            Response::json(['error' => 'lead_token и cuid обязательны'], 422);
            return;
        }

        (new MarketingRepository())->upsertMessengerCuid($leadToken, $messenger, $cuid);
        Response::json(['ok' => true]);
    }

    public function routeCrm(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin', 'sales']);
        $payload = $this->readJsonBody();
        $provider = (new EmailAutomationService())->normalizeCrmProvider((string) ($payload['provider'] ?? 'hubspot'));
        $leadToken = (string) ($payload['lead_token'] ?? '');
        $crmPayload = (array) ($payload['payload'] ?? []);

        if ($leadToken === '') {
            Response::json(['error' => 'lead_token обязателен'], 422);
            return;
        }

        $inserted = (new MarketingRepository())->routeCrm($provider, $leadToken, $crmPayload);
        Response::json(['dispatched' => $inserted, 'idempotent' => !$inserted]);
    }


    public function processCrmQueue(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin']);
        $provider = (new EmailAutomationService())->normalizeCrmProvider((string) ($_GET['provider'] ?? 'hubspot'));
        $result = (new MarketingRepository())->processCrmQueue($provider, 100);
        Response::json(['provider' => $provider, 'result' => $result]);
    }

    public function dlqSummary(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin', 'sales']);
        Response::json((new MarketingRepository())->queueDlqSummary());
    }

    public function queue(): void
    {
        (new RbacAuthService())->requireRole(['owner', 'admin', 'sales']);
        $items = (new MarketingRepository())->queuedEmails();
        Response::json(['queue' => $items]);
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
