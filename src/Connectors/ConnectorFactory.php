<?php

declare(strict_types=1);

namespace App\Connectors;

use InvalidArgumentException;

final class ConnectorFactory
{
    public static function make(string $provider): ConnectorInterface
    {
        return match (strtolower($provider)) {
            'facebook' => new FacebookConnector(),
            'instagram' => new InstagramConnector(),
            'tiktok' => new TikTokConnector(),
            'telegram' => new TelegramConnector(),
            'reddit' => new RedditConnector(),
            'pinterest' => new PinterestConnector(),
            'x', 'twitter' => new XConnector(),
            'threads' => new ThreadsConnector(),
            default => throw new InvalidArgumentException('Unknown provider: ' . $provider),
        };
    }
}
