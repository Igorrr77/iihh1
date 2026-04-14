<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\TokenCipher;
use App\Repositories\BotRepository;
use App\Telegram\TelegramClient;

final class BotService
{
    public function __construct(private readonly array $telegramConfig, private readonly BotRepository $bots)
    {
    }

    public function create(array $data): array
    {
        $secret = bin2hex(random_bytes(16));
        $encrypted = TokenCipher::encrypt($data['token']);

        return $this->bots->create([
            'account_id' => $data['account_id'],
            'project_id' => $data['project_id'],
            'created_by' => $data['created_by'],
            'name' => $data['name'],
            'token_encrypted' => $encrypted,
            'webhook_secret' => $secret,
        ]);
    }

    public function action(int $botId, string $action): array
    {
        $bot = $this->bots->find($botId);
        if (!$bot) {
            return ['error' => 'bot_not_found'];
        }

        $client = new TelegramClient(TokenCipher::decrypt($bot['token_encrypted']));
        return match ($action) {
            'verify' => $client->call('getMe'),
            'set-webhook' => $this->setWebhook($bot, $client),
            'delete-webhook' => $client->call('deleteWebhook'),
            default => ['error' => 'unknown_action'],
        };
    }

    private function setWebhook(array $bot, TelegramClient $client): array
    {
        $url = rtrim($this->telegramConfig['webhook_base'], '/') . '?bot_id=' . $bot['id'];
        $result = $client->call('setWebhook', ['url' => $url, 'secret_token' => $bot['webhook_secret']]);
        $this->bots->updateWebhook((int)$bot['id'], $url, $result['ok'] ? 'set' : 'error');
        return $result + ['webhook_url' => $url];
    }

}
