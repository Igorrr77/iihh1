<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\AuditLogRepository;
use App\Models\ChatRepository;
use App\Models\WebinarRepository;
use App\Services\AdminAuthService;
use App\Services\AiChatReplyService;
use App\Services\ChatModerationService;

final class ChatController
{
    public function send(): void
    {
        $startedAt = microtime(true);
        $payload = $this->readJsonBody();
        $webinar = $this->resolveWebinar((string) ($payload['webinar_id'] ?? ''));
        if ($webinar === null) {
            return;
        }

        $leadToken = (string) ($payload['lead_token'] ?? '');
        $name = (string) ($payload['name'] ?? 'User');
        $message = trim((string) ($payload['message'] ?? ''));

        if ($leadToken === '' || $message === '') {
            Response::json(['error' => 'lead_token и message обязательны'], 422);
            return;
        }

        $repo = new ChatRepository();
        if ($repo->isBanned((int) $webinar['id'], $leadToken)) {
            $repo->recordMetric((int) $webinar['id'], 'api', (int) ((microtime(true) - $startedAt) * 1000), true);
            Response::json(['error' => 'Пользователь забанен'], 403);
            return;
        }

        if ($repo->isMuted((int) $webinar['id'], $leadToken)) {
            $repo->recordMetric((int) $webinar['id'], 'api', (int) ((microtime(true) - $startedAt) * 1000), true);
            Response::json(['error' => 'Пользователь временно muted'], 403);
            return;
        }

        $moderation = new ChatModerationService();
        $isVisible = $moderation->isAllowed($message);
        $repo->addMessage((int) $webinar['id'], $leadToken, $name, $message, $isVisible);
        $repo->recordMetric((int) $webinar['id'], 'api', (int) ((microtime(true) - $startedAt) * 1000), false);

        Response::json(['ok' => true, 'moderation' => $isVisible ? 'passed' : 'hidden']);
    }

    public function list(): void
    {
        $startedAt = microtime(true);
        $payload = $this->readJsonBody();
        $webinar = $this->resolveWebinar((string) ($payload['webinar_id'] ?? ''));
        if ($webinar === null) {
            return;
        }

        $leadToken = (string) ($payload['lead_token'] ?? '');
        $individualMode = (bool) ($payload['individual_mode'] ?? false);
        $sinceId = max(0, (int) ($payload['since_id'] ?? 0));

        $repo = new ChatRepository();
        $messages = $repo->listMessages((int) $webinar['id'], $leadToken !== '' ? $leadToken : null, $individualMode, $sinceId);
        $repo->recordMetric((int) $webinar['id'], 'polling', (int) ((microtime(true) - $startedAt) * 1000), false);

        Response::json(['messages' => $messages]);
    }

    public function stream(): void
    {
        $startedAt = microtime(true);
        $webinarId = (string) ($_GET['webinar_id'] ?? '');
        $leadToken = (string) ($_GET['lead_token'] ?? '');
        $individualMode = ((string) ($_GET['individual_mode'] ?? '0')) === '1';
        $sinceId = max(0, (int) ($_GET['since_id'] ?? 0));

        $webinar = $this->resolveWebinar($webinarId);
        if ($webinar === null) {
            return;
        }

        $repo = new ChatRepository();
        $messages = $repo->listMessages((int) $webinar['id'], $leadToken !== '' ? $leadToken : null, $individualMode, $sinceId);

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $clusterNode = (string) (getenv('CHAT_NODE_ID') ?: gethostname() ?: 'node-1');
        echo 'id: ' . ($sinceId + count($messages)) . "\n";
        echo 'event: chat' . "\n";
        echo 'data: ' . json_encode(['cluster_node' => $clusterNode, 'messages' => $messages], JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();

        $repo->recordMetric((int) $webinar['id'], 'sse', (int) ((microtime(true) - $startedAt) * 1000), false);
    }

    public function metrics(): void
    {
        (new AdminAuthService())->requireAdmin();
        $webinar = $this->resolveWebinar((string) ($_GET['webinar_id'] ?? ''));
        if ($webinar === null) {
            return;
        }

        $repo = new ChatRepository();
        Response::json([
            'p95_latency_ms' => $repo->metricP95Ms((int) $webinar['id']),
            'delivery_errors' => $repo->errorCount((int) $webinar['id']),
        ]);
    }

    public function askAi(): void
    {
        $payload = $this->readJsonBody();
        $webinar = $this->resolveWebinar((string) ($payload['webinar_id'] ?? ''));
        if ($webinar === null) {
            return;
        }

        $leadToken = (string) ($payload['lead_token'] ?? '');
        $question = (string) ($payload['question'] ?? '');
        $policy = (string) ($payload['prompt_policy'] ?? '');
        $replyName = (string) ($payload['reply_name'] ?? 'Модератор');

        if ($leadToken === '' || $question === '') {
            Response::json(['error' => 'lead_token и question обязательны'], 422);
            return;
        }

        $reply = (new AiChatReplyService())->generateReply($question, $policy, $replyName);
        if ($reply === null) {
            Response::json(['ok' => true, 'reply' => null]);
            return;
        }

        (new ChatRepository())->addMessage((int) $webinar['id'], $leadToken, (string) $reply['name'], (string) $reply['text'], true, true);
        (new AuditLogRepository())->write('ai_chat', 'ai_reply_moderation_audit', [
            'webinar_id' => (string) $payload['webinar_id'],
            'lead_token' => $leadToken,
            'question' => $question,
            'reply' => $reply['text'],
            'policy' => $policy,
        ]);
        Response::json(['ok' => true, 'reply' => $reply]);
    }

    public function moderate(): void
    {
        (new AdminAuthService())->requireAdmin();
        $payload = $this->readJsonBody();
        $action = (string) ($payload['action'] ?? '');

        $repo = new ChatRepository();
        if ($action === 'hide') {
            $messageId = (int) ($payload['message_id'] ?? 0);
            if ($messageId <= 0) {
                Response::json(['error' => 'message_id обязателен'], 422);
                return;
            }
            $repo->hideMessage($messageId);
            Response::json(['ok' => true]);
            return;
        }

        if ($action === 'ban' || $action === 'mute') {
            $webinar = $this->resolveWebinar((string) ($payload['webinar_id'] ?? ''));
            if ($webinar === null) {
                return;
            }
            $leadToken = (string) ($payload['lead_token'] ?? '');
            if ($leadToken === '') {
                Response::json(['error' => 'lead_token обязателен'], 422);
                return;
            }

            if ($action === 'ban') {
                $repo->banLead((int) $webinar['id'], $leadToken, (string) ($payload['reason'] ?? 'manual ban'));
                Response::json(['ok' => true]);
                return;
            }

            $durationSec = (int) ($payload['duration_sec'] ?? 300);
            $repo->muteLead((int) $webinar['id'], $leadToken, $durationSec, (string) ($payload['reason'] ?? 'manual mute'));
            Response::json(['ok' => true]);
            return;
        }

        Response::json(['error' => 'Неизвестное действие moderation'], 422);
    }

    private function resolveWebinar(string $externalId): ?array
    {
        if ($externalId === '') {
            Response::json(['error' => 'webinar_id обязателен'], 422);
            return null;
        }

        $webinar = (new WebinarRepository())->findByExternalId($externalId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return null;
        }

        return $webinar;
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
