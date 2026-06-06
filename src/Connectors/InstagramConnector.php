<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Core\Env;

final class InstagramConnector extends AbstractConnector
{
    public function provider(): string
    {
        return 'instagram';
    }

    public function fetch(array $task): array
    {
        $token = $this->token('instagram', 'INSTAGRAM_ACCESS_TOKEN');
        $bizId = Env::get('INSTAGRAM_BUSINESS_ID', '');
        if ($token === '' || $bizId === '') {
            return [];
        }

        $fields = 'id,caption,media_type,media_url,timestamp,like_count,comments_count';
        $url = "https://graph.facebook.com/v23.0/{$bizId}/media?fields={$fields}&limit=50&access_token=" . urlencode($token ?: '');
        $resp = $this->request('GET', $url);
        $items = [];

        foreach (($resp['data']['data'] ?? []) as $d) {
            $type = (($d['media_type'] ?? '') === 'VIDEO' || ($d['media_type'] ?? '') === 'REEL') ? 'reel' : 'post';
            if (!$this->allowedType($task, $type)) {
                continue;
            }
            $text = (string) ($d['caption'] ?? '');
            if (($task['mode'] ?? 'topic') === 'topic' && ($task['query_text'] ?? '') !== '') {
                if (!str_contains(mb_strtolower($text), mb_strtolower((string) $task['query_text']))) {
                    continue;
                }
            }
            $pop = [
                'likes' => (int) ($d['like_count'] ?? 0),
                'comments' => (int) ($d['comments_count'] ?? 0),
                'views' => 0,
            ];
            if (!$this->passPopularity($task, $pop)) {
                continue;
            }

            $items[] = [
                'external_id' => (string) ($d['id'] ?? ''),
                'content_type' => $type,
                'author' => (string) ($task['account_handle'] ?? ''),
                'title' => '',
                'body' => $text,
                'popularity' => $pop,
                'media' => ['url' => $d['media_url'] ?? null],
                'published_at' => isset($d['timestamp']) ? gmdate('Y-m-d H:i:s', strtotime((string) $d['timestamp'])) : $this->nowSql(),
                'raw' => $d,
            ];
        }

        return $items;
    }
}
