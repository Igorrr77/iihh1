<?php

declare(strict_types=1);

namespace App\Repositories;

final class ChatRepository extends BaseRepository
{
    public function list(int $accountId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM chats WHERE account_id=:account_id ORDER BY id DESC LIMIT 200');
        $stmt->execute(['account_id' => $accountId]);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $accountId): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM chats WHERE id=:id AND account_id=:account_id LIMIT 1');
        $stmt->execute(['id' => $id, 'account_id' => $accountId]);
        return $stmt->fetch() ?: null;
    }

    public function messages(int $chatId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM chat_messages WHERE chat_id=:chat_id ORDER BY id DESC LIMIT 100');
        $stmt->execute(['chat_id' => $chatId]);
        return array_reverse($stmt->fetchAll());
    }

    public function addMessage(array $data): void
    {
        $stmt = $this->pdo()->prepare('INSERT INTO chat_messages (chat_id, contact_id, bot_id, direction, telegram_message_id, message_type, text_content, payload_json, status, created_at, updated_at) VALUES (:chat_id,:contact_id,:bot_id,:direction,:telegram_message_id,:message_type,:text_content,:payload_json,:status,NOW(),NOW())');
        $stmt->execute([
            'chat_id' => $data['chat_id'],
            'contact_id' => $data['contact_id'],
            'bot_id' => $data['bot_id'],
            'direction' => $data['direction'],
            'telegram_message_id' => $data['telegram_message_id'] ?? null,
            'message_type' => $data['message_type'] ?? 'text',
            'text_content' => $data['text_content'] ?? null,
            'payload_json' => json_encode($data['payload'] ?? [], JSON_UNESCAPED_UNICODE),
            'status' => $data['status'] ?? 'sent',
        ]);
    }

    public function setMode(int $chatId, string $mode): void
    {
        $stmt = $this->pdo()->prepare('UPDATE chats SET mode=:mode, updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $chatId, 'mode' => $mode]);
    }
}
