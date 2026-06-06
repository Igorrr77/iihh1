<?php

declare(strict_types=1);

namespace Commentor;

use PDO;

final class PlatformClient
{
    public function __construct(
        private readonly ?PDO $pdo = null,
        private readonly ?Logger $logger = null,
    ) {
    }

    public function postReply(string $platform, array $account, array $comment, string $replyText): array
    {
        $account = $this->ensureValidAccessToken($account);

        return match ($platform) {
            'instagram', 'facebook', 'facebook_page' => $this->postMetaReply($account, $comment, $replyText),
            'tiktok' => $this->postTikTokReply($account, $comment, $replyText),
            default => throw new \RuntimeException('Неподдерживаемая платформа: ' . $platform),
        };
    }

    private function ensureValidAccessToken(array $account): array
    {
        $encryptedAccessToken = trim((string) ($account['access_token'] ?? ''));
        if ($encryptedAccessToken !== '') {
            $account['access_token'] = Crypto::decrypt($encryptedAccessToken);
        }

        $expiresAt = (int) ($account['token_expires_at'] ?? 0);
        if ($expiresAt === 0 || $expiresAt > (time() + 120)) {
            return $account;
        }

        $refreshTokenEnc = trim((string) ($account['refresh_token'] ?? ''));
        if ($refreshTokenEnc === '') {
            return $account;
        }

        $metadata = json_decode((string) ($account['metadata_json'] ?? '{}'), true);
        $tokenUrl = trim((string) ($metadata['oauth_token_url'] ?? ''));
        $clientId = trim((string) ($metadata['oauth_client_id'] ?? ''));
        $clientSecret = trim((string) ($metadata['oauth_client_secret'] ?? ''));

        if ($tokenUrl === '' || $clientId === '' || $clientSecret === '') {
            return $account;
        }

        $refreshToken = Crypto::decrypt($refreshTokenEnc);
        $response = $this->postForm($tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (($response['http_code'] ?? 500) >= 400) {
            $this->logger?->warning('Token refresh failed', ['account_id' => $account['id'] ?? null, 'response' => $response['json'] ?? []]);
            return $account;
        }

        $newAccessToken = (string) ($response['json']['access_token'] ?? '');
        if ($newAccessToken === '') {
            return $account;
        }

        $newRefresh = (string) ($response['json']['refresh_token'] ?? $refreshToken);
        $newExpiry = time() + (int) ($response['json']['expires_in'] ?? 3600);

        if ($this->pdo !== null && isset($account['id'])) {
            $stmt = $this->pdo->prepare('UPDATE accounts SET access_token=:access_token, refresh_token=:refresh_token, token_expires_at=:expires WHERE id=:id');
            $stmt->execute([
                ':access_token' => Crypto::encrypt($newAccessToken),
                ':refresh_token' => Crypto::encrypt($newRefresh),
                ':expires' => $newExpiry,
                ':id' => $account['id'],
            ]);
        }

        $account['access_token'] = $newAccessToken;
        $account['refresh_token'] = $newRefresh;
        $account['token_expires_at'] = $newExpiry;

        return $account;
    }

    private function postMetaReply(array $account, array $comment, string $replyText): array
    {
        $token = trim((string) ($account['access_token'] ?? ''));
        $commentId = trim((string) ($comment['external_comment_id'] ?? ''));

        if ($token === '' || $commentId === '') {
            throw new \RuntimeException('Для Meta ответа нужны access_token аккаунта и external_comment_id комментария');
        }

        $graphVersion = Env::get('META_GRAPH_VERSION', 'v22.0');
        $url = sprintf('https://graph.facebook.com/%s/%s/replies', rawurlencode($graphVersion), rawurlencode($commentId));

        $response = $this->postForm($url, [
            'message' => $replyText,
            'access_token' => $token,
        ]);

        if (($response['http_code'] ?? 500) >= 400) {
            $errorMessage = $response['json']['error']['message'] ?? 'Ошибка Meta Graph API';
            throw new \RuntimeException('Meta API: ' . $errorMessage);
        }

        $replyId = (string) ($response['json']['id'] ?? '');
        if ($replyId === '') {
            throw new \RuntimeException('Meta API не вернул id ответа');
        }

        return [
            'posted_reply_id' => $replyId,
            'status' => 'posted',
            'raw_response' => $response['json'],
        ];
    }

    private function postTikTokReply(array $account, array $comment, string $replyText): array
    {
        $metadata = json_decode((string) ($account['metadata_json'] ?? '{}'), true);
        $replyUrl = trim((string) ($metadata['reply_api_url'] ?? ''));
        $bearer = trim((string) ($account['access_token'] ?? ''));

        if ($replyUrl === '' || $bearer === '') {
            throw new \RuntimeException('Для TikTok укажите metadata.reply_api_url и access_token в аккаунте');
        }

        $payload = [
            'comment_id' => (string) ($comment['external_comment_id'] ?? ''),
            'video_id' => (string) ($comment['external_media_id'] ?? ''),
            'text' => $replyText,
        ];

        $response = $this->postJson($replyUrl, $payload, ['Authorization: Bearer ' . $bearer]);
        if (($response['http_code'] ?? 500) >= 400) {
            $errorMessage = $response['json']['error']['message'] ?? 'Ошибка TikTok API';
            throw new \RuntimeException('TikTok API: ' . $errorMessage);
        }

        $replyId = (string) ($response['json']['data']['reply_id'] ?? $response['json']['reply_id'] ?? '');
        if ($replyId === '') {
            throw new \RuntimeException('TikTok API не вернул reply_id');
        }

        return [
            'posted_reply_id' => $replyId,
            'status' => 'posted',
            'raw_response' => $response['json'],
        ];
    }

    private function postForm(string $url, array $fields): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $error !== '') {
            throw new \RuntimeException('Ошибка cURL: ' . $error);
        }

        return [
            'http_code' => $code,
            'json' => json_decode($body, true) ?? [],
            'raw' => $body,
        ];
    }

    private function postJson(string $url, array $payload, array $headers = []): array
    {
        $mergedHeaders = array_merge(['Content-Type: application/json'], $headers);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $mergedHeaders,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $error !== '') {
            throw new \RuntimeException('Ошибка cURL: ' . $error);
        }

        return [
            'http_code' => $code,
            'json' => json_decode($body, true) ?? [],
            'raw' => $body,
        ];
    }
}
