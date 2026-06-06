<?php

declare(strict_types=1);

namespace App\Core;

final class ScopeManager
{
    public static function defaults(string $provider): array
    {
        return match ($provider) {
            'facebook' => ['pages_read_engagement', 'pages_read_user_content'],
            'instagram' => ['instagram_basic', 'pages_show_list', 'instagram_manage_insights'],
            'threads' => ['threads_basic', 'threads_manage_replies'],
            'x' => ['tweet.read', 'users.read', 'offline.access'],
            'tiktok' => ['user.info.basic', 'video.list', 'video.insights'],
            'pinterest' => ['pins:read', 'boards:read', 'user_accounts:read'],
            'reddit' => ['identity', 'read', 'history'],
            default => [],
        };
    }

    public static function normalize(string $raw): array
    {
        $parts = preg_split('/[\s,]+/', trim($raw)) ?: [];
        $parts = array_values(array_unique(array_filter(array_map('trim', $parts), static fn ($v) => $v !== '')));
        sort($parts);
        return $parts;
    }
}
