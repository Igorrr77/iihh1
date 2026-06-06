<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Core\Env;

final class RedditConnector extends AbstractConnector
{
    public function provider(): string
    {
        return 'reddit';
    }

    public function fetch(array $task): array
    {
        $mode = $task['mode'] ?? 'topic';
        if ($mode === 'topic') {
            $q = urlencode((string) ($task['query_text'] ?? 'news'));
            $url = "https://www.reddit.com/search.json?q={$q}&sort=top&limit=50";
        } else {
            $subreddit = ltrim((string) ($task['account_handle'] ?? ''), 'r/');
            $url = "https://www.reddit.com/r/{$subreddit}/new.json?limit=50";
        }

        $resp = $this->request('GET', $url, ['User-Agent' => Env::get('REDDIT_USER_AGENT', 'SocialHarvester/1.0') ?: 'SocialHarvester/1.0']);
        $items = [];

        foreach (($resp['data']['data']['children'] ?? []) as $row) {
            $d = $row['data'] ?? [];
            $type = !empty($d['is_video']) ? 'video' : 'post';
            if (!$this->allowedType($task, $type) && !$this->allowedType($task, 'comment')) {
                continue;
            }

            $pop = [
                'likes' => (int) ($d['ups'] ?? 0),
                'comments' => (int) ($d['num_comments'] ?? 0),
                'views' => (int) ($d['view_count'] ?? 0),
            ];

            if (!$this->passPopularity($task, $pop)) {
                continue;
            }

            $items[] = [
                'external_id' => (string) ($d['id'] ?? ''),
                'content_type' => $type,
                'author' => (string) ($d['author'] ?? ''),
                'title' => (string) ($d['title'] ?? ''),
                'body' => (string) ($d['selftext'] ?? ''),
                'popularity' => $pop,
                'media' => ['url' => $d['url'] ?? null],
                'published_at' => isset($d['created_utc']) ? gmdate('Y-m-d H:i:s', (int) $d['created_utc']) : $this->nowSql(),
                'raw' => $d,
            ];
        }

        return $items;
    }
}
