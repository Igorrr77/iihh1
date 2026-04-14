<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\RuntimeService;
use PDOException;

final class WebhookController
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(Request $request): Response
    {
        $payload = json_decode($request->rawBody(), true);
        if (!is_array($payload)) {
            return Response::json(['ok' => false, 'error' => 'invalid_json'], 400);
        }

        $botId = (int)($request->input('bot_id', 1));
        $updateId = (int)($payload['update_id'] ?? 0);

        if (!$this->isValidSecret($botId, (string)$request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return Response::json(['ok' => false, 'error' => 'invalid_secret'], 403);
        }

        try {
            $db = $this->container->get(Database::class)->pdo();
            $stmt = $db->prepare('INSERT INTO inbound_updates (bot_id, telegram_update_id, update_type, payload_json, payload_hash, received_at, status) VALUES (:bot_id,:update_id,:type,:payload,:hash,NOW(),"received")');
            $stmt->execute([
                'bot_id' => $botId,
                'update_id' => $updateId,
                'type' => array_key_first(array_diff_key($payload, ['update_id' => true])) ?: 'message',
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'hash' => hash('sha256', json_encode($payload)),
            ]);
        } catch (PDOException $e) {
            if ((int)$e->getCode() === 23000) {
                return Response::json(['ok' => true, 'duplicate' => true]);
            }
            return Response::json(['ok' => false, 'error' => 'db_error'], 500);
        }

        $runtime = $this->container->get(RuntimeService::class);
        $runtime->handleTelegramUpdate($botId, $payload);

        return Response::json(['ok' => true]);
    }

    private function isValidSecret(int $botId, string $secretHeader): bool
    {
        if ($botId <= 0) {
            return false;
        }

        $db = $this->container->get(Database::class)->pdo();
        $stmt = $db->prepare('SELECT webhook_secret FROM bots WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $botId]);
        $secret = (string)($stmt->fetchColumn() ?: '');

        if ($secret === '') {
            return true;
        }

        return hash_equals($secret, $secretHeader);
    }
}
