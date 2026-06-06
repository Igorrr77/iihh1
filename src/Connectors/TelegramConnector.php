<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Core\Env;

final class TelegramConnector extends AbstractConnector
{
    public function provider(): string
    {
        return 'telegram';
    }

    public function fetch(array $task): array
    {
        $token = Env::get('TELEGRAM_BOT_TOKEN', '');
        if ($token === '') {
            return [];
        }

        $resp = $this->request('GET', "https://api.telegram.org/bot{$token}/getUpdates");
        $items = [];
        foreach (($resp['data']['result'] ?? []) as $row) {
            $msg = $row['message'] ?? [];
            $text = (string) ($msg['text'] ?? '');

            if (($task['mode'] ?? 'topic') === 'topic' && ($task['query_text'] ?? '') !== '') {
                if (!str_contains(mb_strtolower($text), mb_strtolower((string) $task['query_text']))) {
                    continue;
                }
            }

            $type = !empty($msg['video']) ? 'video' : 'post';
            if (!$this->allowedType($task, $type)) {
                continue;
            }

            $pop = ['likes' => 0, 'comments' => 0, 'views' => 0];
            $items[] = [
                'external_id' => (string) ($row['update_id'] ?? ''),
                'content_type' => $type,
                'author' => (string) ($msg['from']['username'] ?? ''),
                'title' => '',
                'body' => $text,
                'popularity' => $pop,
                'media' => [],
                'published_at' => isset($msg['date']) ? gmdate('Y-m-d H:i:s', (int) $msg['date']) : $this->nowSql(),
                'raw' => $row,
            ];
        }

        return $items;
    }
}
