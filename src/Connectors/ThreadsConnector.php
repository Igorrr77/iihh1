<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Core\Env;

final class ThreadsConnector extends AbstractConnector
{
    public function provider(): string
    {
        return 'threads';
    }

    public function fetch(array $task): array
    {
        $token = $this->token('threads', 'THREADS_ACCESS_TOKEN');
        $userId = Env::get('THREADS_USER_ID', '');
        if ($token === '' || $userId === '') {
            return [];
        }

        $fields = 'id,text,permalink,timestamp,media_type,media_url,reply_audience';
        $url = "https://graph.threads.net/v1.0/{$userId}/threads?fields={$fields}&access_token=" . urlencode($token ?: '');
        $resp = $this->request('GET', $url);

        $items = [];
        foreach (($resp['data']['data'] ?? []) as $d) {
            $text = (string) ($d['text'] ?? '');
            if (($task['mode'] ?? 'topic') === 'topic' && ($task['query_text'] ?? '') !== '') {
                if (!str_contains(mb_strtolower($text), mb_strtolower((string) $task['query_text']))) {
                    continue;
                }
            }

            $type = (($d['media_type'] ?? '') === 'VIDEO') ? 'reel' : 'post';
            if (!$this->allowedType($task, $type)) {
                continue;
            }
            $pop = ['likes' => 0, 'comments' => 0, 'views' => 0];
            if (!$this->passPopularity($task, $pop)) {
                continue;
            }
            $items[] = [
                'external_id' => (string) ($d['id'] ?? ''),
                'content_type' => $type,
                'author' => (string) ($task['account_handle'] ?? $userId),
                'title' => '',
                'body' => $text,
                'popularity' => $pop,
                'media' => ['url' => $d['media_url'] ?? null, 'permalink' => $d['permalink'] ?? null],
                'published_at' => isset($d['timestamp']) ? gmdate('Y-m-d H:i:s', strtotime((string) $d['timestamp'])) : $this->nowSql(),
                'raw' => $d,
            ];
        }

        return $items;
    }
}
