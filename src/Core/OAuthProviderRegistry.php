<?php

declare(strict_types=1);

namespace App\Core;

final class OAuthProviderRegistry
{
    public static function config(string $provider): array
    {
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');

        return match ($provider) {
            'facebook' => [
                'authorize' => 'https://www.facebook.com/v23.0/dialog/oauth',
                'token' => 'https://graph.facebook.com/v23.0/oauth/access_token',
                'client_id' => (string) Env::get('FACEBOOK_CLIENT_ID', ''),
                'client_secret' => (string) Env::get('FACEBOOK_CLIENT_SECRET', ''),
                'scopes_delimiter' => ',',
                'redirect_uri' => $appUrl . '/public/oauth.php?provider=facebook&action=callback',
                'pkce' => false,
            ],
            'instagram' => [
                'authorize' => 'https://www.facebook.com/v23.0/dialog/oauth',
                'token' => 'https://graph.facebook.com/v23.0/oauth/access_token',
                'client_id' => (string) Env::get('INSTAGRAM_CLIENT_ID', ''),
                'client_secret' => (string) Env::get('INSTAGRAM_CLIENT_SECRET', ''),
                'scopes_delimiter' => ',',
                'redirect_uri' => $appUrl . '/public/oauth.php?provider=instagram&action=callback',
                'pkce' => false,
            ],
            'threads' => [
                'authorize' => 'https://threads.net/oauth/authorize',
                'token' => 'https://graph.threads.net/oauth/access_token',
                'client_id' => (string) Env::get('THREADS_CLIENT_ID', ''),
                'client_secret' => (string) Env::get('THREADS_CLIENT_SECRET', ''),
                'scopes_delimiter' => ',',
                'redirect_uri' => $appUrl . '/public/oauth.php?provider=threads&action=callback',
                'pkce' => false,
            ],
            'x' => [
                'authorize' => 'https://twitter.com/i/oauth2/authorize',
                'token' => 'https://api.twitter.com/2/oauth2/token',
                'client_id' => (string) Env::get('X_CLIENT_ID', ''),
                'client_secret' => (string) Env::get('X_CLIENT_SECRET', ''),
                'scopes_delimiter' => ' ',
                'redirect_uri' => $appUrl . '/public/oauth.php?provider=x&action=callback',
                'pkce' => true,
            ],
            'tiktok' => [
                'authorize' => 'https://www.tiktok.com/v2/auth/authorize/',
                'token' => 'https://open.tiktokapis.com/v2/oauth/token/',
                'client_id' => (string) Env::get('TIKTOK_CLIENT_KEY', ''),
                'client_secret' => (string) Env::get('TIKTOK_CLIENT_SECRET', ''),
                'scopes_delimiter' => ',',
                'redirect_uri' => $appUrl . '/public/oauth.php?provider=tiktok&action=callback',
                'pkce' => false,
            ],
            'pinterest' => [
                'authorize' => 'https://www.pinterest.com/oauth/',
                'token' => 'https://api.pinterest.com/v5/oauth/token',
                'client_id' => (string) Env::get('PINTEREST_CLIENT_ID', ''),
                'client_secret' => (string) Env::get('PINTEREST_CLIENT_SECRET', ''),
                'scopes_delimiter' => ',',
                'redirect_uri' => $appUrl . '/public/oauth.php?provider=pinterest&action=callback',
                'pkce' => false,
            ],
            'reddit' => [
                'authorize' => 'https://www.reddit.com/api/v1/authorize',
                'token' => 'https://www.reddit.com/api/v1/access_token',
                'client_id' => (string) Env::get('REDDIT_CLIENT_ID', ''),
                'client_secret' => (string) Env::get('REDDIT_CLIENT_SECRET', ''),
                'scopes_delimiter' => ' ',
                'redirect_uri' => $appUrl . '/public/oauth.php?provider=reddit&action=callback',
                'pkce' => false,
            ],
            default => [],
        };
    }
}
