<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Helpers\TokenCipher;
use App\Repositories\ExecutionRepository;
use App\Telegram\TelegramClient;

final class RuntimeService
{
    public function __construct(
        private readonly Database $database,
        private readonly Logger $logger,
        private readonly ExecutionRepository $executions,
        private readonly RuntimeEngineService $engine,
    ) {
    }

    public function handleTelegramUpdate(int $botId, array $payload): void
    {
        $contactTelegramId = (int)($payload['message']['from']['id'] ?? $payload['callback_query']['from']['id'] ?? 0);
        if ($contactTelegramId <= 0) {
            return;
        }

        $pdo = $this->database->pdo();
        $this->acquireContactLock($contactTelegramId);

        $contact = $this->findOrCreateContact($botId, $contactTelegramId, $payload);
        $resumed = $this->resumeWaitingState((int)$contact['id'], $payload);

        if (!$resumed) {
            $text = (string)($payload['message']['text'] ?? '');
            if ($text === '/start') {
                $this->startProcessForCommand($botId, (int)$contact['id'], (int)$contact['telegram_chat_id'], '/start', $payload);
            }
        }

        $this->logger->info('runtime.update.processed', [
            'bot_id' => $botId,
            'contact_id' => (int)$contact['id'],
            'update_id' => (int)($payload['update_id'] ?? 0),
            'resumed' => $resumed,
        ]);
    }

    private function startProcessForCommand(int $botId, int $contactId, int $telegramChatId, string $command, array $payload): void
    {
        $pdo = $this->database->pdo();
        $stmt = $pdo->prepare('SELECT p.id AS process_id, p.project_id, p.account_id, pv.id AS process_version_id, pv.compiled_graph_json, b.token_encrypted FROM processes p JOIN process_versions pv ON pv.id = p.active_version_id JOIN bots b ON b.id = p.bot_id WHERE p.bot_id=:bot_id AND p.status="published" LIMIT 1');
        $stmt->execute(['bot_id' => $botId]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }

        $compiled = json_decode((string)$row['compiled_graph_json'], true);
        if (!is_array($compiled)) {
            return;
        }

        $telegram = new TelegramClient(TokenCipher::decrypt((string)$row['token_encrypted']));
        $this->engine->runCompiled($compiled, [
            'account_id' => (int)$row['account_id'],
            'project_id' => (int)$row['project_id'],
            'bot_id' => $botId,
            'process_id' => (int)$row['process_id'],
            'process_version_id' => (int)$row['process_version_id'],
            'contact_id' => $contactId,
            'telegram_chat_id' => $telegramChatId,
            'trigger_type' => 'command',
            'trigger_ref' => $command,
            'trigger_payload' => $payload,
        ], $telegram);
    }

    private function acquireContactLock(int $contactTelegramId): void
    {
        $pdo = $this->database->pdo();
        $lockKey = 'contact:' . $contactTelegramId;
        $owner = bin2hex(random_bytes(8));
        $stmt = $pdo->prepare('INSERT INTO locks (lock_key, owner_token, expires_at, created_at, updated_at) VALUES (:lock_key,:owner,DATE_ADD(NOW(), INTERVAL 60 SECOND),NOW(),NOW()) ON DUPLICATE KEY UPDATE owner_token = IF(expires_at < NOW(), VALUES(owner_token), owner_token), expires_at = IF(expires_at < NOW(), VALUES(expires_at), expires_at), updated_at = NOW()');
        $stmt->execute(['lock_key' => $lockKey, 'owner' => $owner]);
    }

    private function findOrCreateContact(int $botId, int $telegramId, array $payload): array
    {
        $pdo = $this->database->pdo();
        $stmt = $pdo->prepare('SELECT * FROM contacts WHERE bot_id=:bot_id AND telegram_user_id=:telegram_user_id LIMIT 1');
        $stmt->execute(['bot_id' => $botId, 'telegram_user_id' => $telegramId]);
        $contact = $stmt->fetch();
        if ($contact) {
            return $contact;
        }

        $firstName = $payload['message']['from']['first_name'] ?? null;
        $stmt = $pdo->prepare('INSERT INTO contacts (account_id, project_id, bot_id, telegram_user_id, telegram_chat_id, first_name, status, created_at, updated_at) VALUES (1,1,:bot_id,:telegram_user_id,:telegram_chat_id,:first_name,"active",NOW(),NOW())');
        $stmt->execute([
            'bot_id' => $botId,
            'telegram_user_id' => $telegramId,
            'telegram_chat_id' => (int)($payload['message']['chat']['id'] ?? $telegramId),
            'first_name' => $firstName,
        ]);

        $stmt = $pdo->prepare('SELECT * FROM contacts WHERE id=:id');
        $stmt->execute(['id' => (int)$pdo->lastInsertId()]);
        return $stmt->fetch() ?: [];
    }

    private function resumeWaitingState(int $contactId, array $payload): bool
    {
        $pdo = $this->database->pdo();
        $stmt = $pdo->prepare('SELECT * FROM waiting_states WHERE contact_id=:contact_id AND status="active" ORDER BY id ASC LIMIT 1');
        $stmt->execute(['contact_id' => $contactId]);
        $state = $stmt->fetch();
        if (!$state) {
            return false;
        }

        $value = $payload['message']['text'] ?? null;
        $update = $pdo->prepare('UPDATE waiting_states SET status="resolved", updated_at=NOW() WHERE id=:id');
        $update->execute(['id' => $state['id']]);

        $executionId = (int)$state['execution_id'];

        $executionStmt = $pdo->prepare('SELECT e.*, pv.compiled_graph_json, b.token_encrypted FROM executions e JOIN process_versions pv ON pv.id=e.process_version_id JOIN bots b ON b.id=e.bot_id WHERE e.id=:id LIMIT 1');
        $executionStmt->execute(['id' => $executionId]);
        $execution = $executionStmt->fetch();
        if (!$execution) {
            $this->executions->step($executionId, 0, (string)$state['node_uuid'], 'wait_input', 'completed', $payload, ['captured' => $value]);
            $this->executions->setStatus($executionId, 'completed', (string)$state['node_uuid']);
            return true;
        }

        $this->executions->step($executionId, (int)$execution['process_version_id'], (string)$state['node_uuid'], 'wait_input', 'completed', $payload, ['captured' => $value]);

        $compiled = json_decode((string)$execution['compiled_graph_json'], true);
        if (!is_array($compiled)) {
            $this->executions->setStatus($executionId, 'completed', (string)$state['node_uuid']);
            return true;
        }

        $context = json_decode((string)$execution['context_json'], true);
        if (!is_array($context)) {
            $context = [];
        }
        $saveTo = (string)($state['save_to_key'] ?? 'input');
        $context['vars'][$saveTo] = $value;
        $context['process_version_id'] = (int)$execution['process_version_id'];
        if (!isset($context['telegram_chat_id']) || (int)$context['telegram_chat_id'] <= 0) {
            $contactStmt = $pdo->prepare('SELECT telegram_chat_id FROM contacts WHERE id=:id LIMIT 1');
            $contactStmt->execute(['id' => (int)$execution['contact_id']]);
            $context['telegram_chat_id'] = (int)($contactStmt->fetchColumn() ?: 0);
        }
        $this->executions->updateContext($executionId, $context);

        $currentNode = $compiled['nodes'][(string)$state['node_uuid']] ?? null;
        $nextNode = null;
        $successPort = (string)($state['success_port'] ?? 'success');
        foreach ($currentNode['next'] ?? [] as $edge) {
            if ((string)($edge['port'] ?? '') === $successPort) {
                $nextNode = $edge['target'] ?? null;
                break;
            }
        }
        if (!is_string($nextNode) || $nextNode === '') {
            $nextNode = $currentNode['next'][0]['target'] ?? null;
        }
        if (!is_string($nextNode) || $nextNode === '') {
            $this->executions->setStatus($executionId, 'completed', (string)$state['node_uuid']);
            return true;
        }

        $telegram = new TelegramClient(TokenCipher::decrypt((string)$execution['token_encrypted']));
        $this->engine->resumeCompiled($executionId, $compiled, $context, $nextNode, $telegram);
        return true;
    }
}
