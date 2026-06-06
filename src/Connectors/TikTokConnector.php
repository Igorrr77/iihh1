<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Core\Env;

final class TikTokConnector extends AbstractConnector
{
    public function provider(): string
    {
        return 'tiktok';
    }

    public function fetch(array $task): array
    {
        $token = $this->token('tiktok', 'TIKTOK_ACCESS_TOKEN');
        if ($token === '') {
            return [];
        }

        $queryText = (string) ($task['query_text'] ?? '');
        $payload = [
            'query' => [
                'and' => $queryText !== '' ? [['operation' => 'IN', 'field_name' => 'keyword', 'field_values' => [$queryText]]] : [],
            ],
            'max_count' => 50,
        ];

        $resp = $this->request('POST', 'https://open.tiktokapis.com/v2/research/video/query/', [
            'Authorization' => 'Bearer ' . $token,
        ], $payload);

        $items = [];
        foreach (($resp['data']['data']['videos'] ?? []) as $d) {
            $type = 'video';
            if (!$this->allowedType($task, $type)) {
                continue;
            }
            $pop = [
                'likes' => (int) ($d['like_count'] ?? 0),
                'comments' => (int) ($d['comment_count'] ?? 0),
                'views' => (int) ($d['view_count'] ?? 0),
            ];
            if (!$this->passPopularity($task, $pop)) {
                continue;
            }
            $items[] = [
                'external_id' => (string) ($d['id'] ?? ''),
                'content_type' => 'video',
                'author' => (string) ($d['username'] ?? ''),
                'title' => '',
                'body' => (string) ($d['video_description'] ?? ''),
                'popularity' => $pop,
                'media' => ['url' => $d['embed_url'] ?? null],
                'published_at' => isset($d['create_time']) ? gmdate('Y-m-d H:i:s', (int) $d['create_time']) : $this->nowSql(),
                'raw' => $d,
            ];
        }

        return $items;
    }
}
