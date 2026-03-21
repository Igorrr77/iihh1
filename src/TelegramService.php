<?php

declare(strict_types=1);

namespace App;

final class TelegramService
{
    public function __construct(private Database $db, private AnalysisService $analysis)
    {
    }

    public function handleWebhook(string $tenantSlug, string $secret, array $update, string $globalSecret): void
    {
        if (!hash_equals($globalSecret, $secret)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $tenant = $this->db->query('SELECT id, telegram_bot_token FROM tenants WHERE slug = :slug LIMIT 1', ['slug' => $tenantSlug])->fetch();
        if (!$tenant) {
            http_response_code(404);
            echo 'Unknown tenant';
            return;
        }

        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (!$message) {
            echo 'ok';
            return;
        }

        $chatId = (int) ($message['chat']['id'] ?? 0);
        $from = $message['from']['username'] ?? ($message['from']['first_name'] ?? 'unknown');
        $text = trim((string) ($message['text'] ?? ''));

        if ($chatId === 0 || $text === '') {
            echo 'ok';
            return;
        }

        $conversation = $this->db->query(
            'SELECT id FROM conversations WHERE tenant_id = :tenant_id AND chat_id = :chat_id ORDER BY id DESC LIMIT 1',
            ['tenant_id' => (int) $tenant['id'], 'chat_id' => $chatId]
        )->fetch();

        if (!$conversation) {
            $this->db->query(
                'INSERT INTO conversations (tenant_id, chat_id, client_handle, created_at, updated_at) VALUES (:tenant_id, :chat_id, :client_handle, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
                ['tenant_id' => (int) $tenant['id'], 'chat_id' => $chatId, 'client_handle' => (string) $from]
            );
            $conversationId = (int) $this->db->pdo()->lastInsertId();
        } else {
            $conversationId = (int) $conversation['id'];
            $this->db->query('UPDATE conversations SET updated_at = UTC_TIMESTAMP() WHERE id = :id', ['id' => $conversationId]);
        }

        $analysis = $this->analysis->handleTranscriptChunk((int) $tenant['id'], $conversationId, $text, 'auto');

        $autoReply = $this->composeReply($analysis);
        $this->sendMessage((string) $tenant['telegram_bot_token'], $chatId, $autoReply);

        $this->db->query(
            'INSERT INTO outbound_messages (tenant_id, conversation_id, message_text, sent_at) VALUES (:tenant_id, :conversation_id, :message_text, UTC_TIMESTAMP())',
            ['tenant_id' => (int) $tenant['id'], 'conversation_id' => $conversationId, 'message_text' => $autoReply]
        );

        $this->dispatchWebhook((int) $tenant['id'], $conversationId, $analysis);

        echo 'ok';
    }

    private function composeReply(array $analysis): string
    {
        $pattern = $analysis['response_patterns'][0] ?? 'Спасибо за ваш вопрос. Давайте подберем лучший вариант под ваш запрос.';
        $painPoint = $analysis['pain_points'][0] ?? 'вашу главную цель';

        return str_replace('{pain_point}', (string) $painPoint, (string) $pattern);
    }

    private function sendMessage(string $botToken, int $chatId, string $text): void
    {
        $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $botToken);
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 15,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function dispatchWebhook(int $tenantId, int $conversationId, array $analysis): void
    {
        $rows = $this->db->query(
            'SELECT webhook_url, crm_endpoint FROM tenant_integrations WHERE tenant_id = :tenant_id',
            ['tenant_id' => $tenantId]
        )->fetchAll();
        foreach ($rows as $row) {
            foreach (['webhook_url', 'crm_endpoint'] as $field) {
                $url = (string) ($row[$field] ?? '');
                if ($url === '') {
                    continue;
                }

                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS => json_encode([
                        'tenant_id' => $tenantId,
                        'conversation_id' => $conversationId,
                        'analysis' => $analysis,
                    ], JSON_UNESCAPED_UNICODE),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                ]);
                curl_exec($ch);
                curl_close($ch);
            }
        }
    }
}
