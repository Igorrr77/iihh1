<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Core\Env;

final class XConnector extends AbstractConnector
{
    public function provider(): string
    {
        return 'x';
    }

    public function fetch(array $task): array
    {
        $token = $this->token('x', 'X_BEARER_TOKEN');
        if ($token === '') {
            return [];
        }

        $query = ($task['mode'] ?? 'topic') === 'topic'
            ? ((string) ($task['query_text'] ?? 'news') . ' lang:en -is:retweet')
            : ('from:' . (string) ($task['account_handle'] ?? ''));

        $url = 'https://api.twitter.com/2/tweets/search/recent?tweet.fields=public_metrics,created_at,author_id&max_results=100&query=' . urlencode($query);
        $resp = $this->request('GET', $url, ['Authorization' => 'Bearer ' . $token]);

        $items = [];
        foreach (($resp['data']['data'] ?? []) as $d) {
            $type = 'post';
            if (!$this->allowedType($task, $type) && !$this->allowedType($task, 'comment')) {
                continue;
            }
            $metrics = $d['public_metrics'] ?? [];
            $pop = [
                'likes' => (int) ($metrics['like_count'] ?? 0),
                'comments' => (int) ($metrics['reply_count'] ?? 0),
                'views' => (int) ($metrics['impression_count'] ?? 0),
            ];
            if (!$this->passPopularity($task, $pop)) {
                continue;
            }

            $items[] = [
                'external_id' => (string) ($d['id'] ?? ''),
                'content_type' => 'post',
                'author' => (string) ($d['author_id'] ?? ''),
                'title' => '',
                'body' => (string) ($d['text'] ?? ''),
                'popularity' => $pop,
                'media' => [],
                'published_at' => isset($d['created_at']) ? gmdate('Y-m-d H:i:s', strtotime((string) $d['created_at'])) : $this->nowSql(),
                'raw' => $d,
            ];
        }

        return $items;
    }
}
