<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\TokenCipher;
use App\Repositories\BotRepository;
use App\Repositories\ChatRepository;
use App\Telegram\TelegramClient;

final class ChatService
{
    public function __construct(private readonly ChatRepository $chats, private readonly BotRepository $bots)
    {
    }

    public function sendMessage(array $chat, string $text): array
    {
        $bot = $this->bots->find((int)$chat['bot_id']);
        if (!$bot) {
            return ['error' => 'bot_not_found'];
        }

        $client = new TelegramClient(TokenCipher::decrypt($bot['token_encrypted']));
        $result = $client->call('sendMessage', [
            'chat_id' => $chat['telegram_chat_id'],
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        $this->chats->addMessage([
            'chat_id' => $chat['id'],
            'contact_id' => $chat['contact_id'],
            'bot_id' => $chat['bot_id'],
            'direction' => 'operator',
            'telegram_message_id' => $result['body']['result']['message_id'] ?? null,
            'message_type' => 'text',
            'text_content' => $text,
            'payload' => $result,
            'status' => ($result['ok'] ?? false) ? 'sent' : 'failed',
        ]);

        return $result;
    }
}
