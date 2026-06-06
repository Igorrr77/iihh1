<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Core\Env;

final class FacebookConnector extends AbstractConnector
{
    public function provider(): string
    {
        return 'facebook';
    }

    public function fetch(array $task): array
    {
        $token = $this->token('facebook', 'FACEBOOK_ACCESS_TOKEN');
        $pageId = Env::get('FACEBOOK_PAGE_ID', '');
        if ($token === '' || $pageId === '') {
            return [];
        }

        $fields = 'id,message,created_time,permalink_url,likes.summary(true),comments.summary(true),attachments';
        $url = "https://graph.facebook.com/v23.0/{$pageId}/posts?fields={$fields}&limit=50&access_token=" . urlencode($token ?: '');
        $resp = $this->request('GET', $url);

        $items = [];
        foreach (($resp['data']['data'] ?? []) as $d) {
            $text = (string) ($d['message'] ?? '');
            if (($task['mode'] ?? 'topic') === 'topic' && ($task['query_text'] ?? '') !== '') {
                if (!str_contains(mb_strtolower($text), mb_strtolower((string) $task['query_text']))) {
                    continue;
                }
            }

            $pop = [
                'likes' => (int) ($d['likes']['summary']['total_count'] ?? 0),
                'comments' => (int) ($d['comments']['summary']['total_count'] ?? 0),
                'views' => 0,
            ];
            if (!$this->passPopularity($task, $pop) || !$this->allowedType($task, 'post')) {
                continue;
            }

            $items[] = [
                'external_id' => (string) ($d['id'] ?? ''),
                'content_type' => 'post',
                'author' => (string) ($task['account_handle'] ?? $pageId),
                'title' => '',
                'body' => $text,
                'popularity' => $pop,
                'media' => ['attachments' => $d['attachments'] ?? []],
                'published_at' => isset($d['created_time']) ? gmdate('Y-m-d H:i:s', strtotime((string) $d['created_time'])) : $this->nowSql(),
                'raw' => $d,
            ];
        }

        return $items;
    }
}
