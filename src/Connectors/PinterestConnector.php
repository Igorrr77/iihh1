<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Core\Env;

final class PinterestConnector extends AbstractConnector
{
    public function provider(): string
    {
        return 'pinterest';
    }

    public function fetch(array $task): array
    {
        $token = $this->token('pinterest', 'PINTEREST_ACCESS_TOKEN');
        if ($token === '') {
            return [];
        }

        $query = urlencode((string) ($task['query_text'] ?? 'design'));
        $resp = $this->request('GET', "https://api.pinterest.com/v5/pins/search?query={$query}&page_size=50", [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $items = [];
        foreach (($resp['data']['items'] ?? []) as $d) {
            $type = (($d['media']['media_type'] ?? '') === 'video') ? 'video' : 'post';
            if (!$this->allowedType($task, $type)) {
                continue;
            }
            $pop = ['likes' => 0, 'comments' => 0, 'views' => 0];
            $items[] = [
                'external_id' => (string) ($d['id'] ?? ''),
                'content_type' => $type,
                'author' => (string) ($d['creator_id'] ?? ''),
                'title' => (string) ($d['title'] ?? ''),
                'body' => (string) ($d['description'] ?? ''),
                'popularity' => $pop,
                'media' => ['url' => $d['media']['images']['orig']['url'] ?? null],
                'published_at' => isset($d['created_at']) ? gmdate('Y-m-d H:i:s', strtotime((string) $d['created_at'])) : $this->nowSql(),
                'raw' => $d,
            ];
        }

        return $items;
    }
}
