<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class OAuthAccountRepository
{
    private Crypto $crypto;

    public function __construct(private readonly PDO $pdo)
    {
        $this->crypto = new Crypto();
    }

    public function upsert(string $provider, string $providerUserId, string $label, array $scopes, string $accessToken, ?string $refreshToken, ?int $expiresIn, ?int $refreshExpiresIn, string $tokenType = 'Bearer'): void
    {
        $expiresAt = $expiresIn ? gmdate('Y-m-d H:i:s', time() + $expiresIn) : null;
        $refreshExpiresAt = $refreshExpiresIn ? gmdate('Y-m-d H:i:s', time() + $refreshExpiresIn) : null;

        $stmt = $this->pdo->prepare('INSERT INTO oauth_accounts(provider, provider_user_id, account_label, scopes_json, access_token_enc, refresh_token_enc, token_type, expires_at, refresh_expires_at, status, created_at, updated_at)
            VALUES(:provider, :provider_user_id, :account_label, :scopes_json, :access_token_enc, :refresh_token_enc, :token_type, :expires_at, :refresh_expires_at, "active", UTC_TIMESTAMP(), UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE account_label=VALUES(account_label), scopes_json=VALUES(scopes_json), access_token_enc=VALUES(access_token_enc), refresh_token_enc=VALUES(refresh_token_enc), token_type=VALUES(token_type), expires_at=VALUES(expires_at), refresh_expires_at=VALUES(refresh_expires_at), status="active", updated_at=UTC_TIMESTAMP()');

        $stmt->execute([
            ':provider' => $provider,
            ':provider_user_id' => $providerUserId,
            ':account_label' => $label,
            ':scopes_json' => json_encode($scopes, JSON_UNESCAPED_UNICODE),
            ':access_token_enc' => $this->crypto->encrypt($accessToken),
            ':refresh_token_enc' => $refreshToken ? $this->crypto->encrypt($refreshToken) : null,
            ':token_type' => $tokenType,
            ':expires_at' => $expiresAt,
            ':refresh_expires_at' => $refreshExpiresAt,
        ]);
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT id, provider, provider_user_id, account_label, scopes_json, token_type, expires_at, refresh_expires_at, status, updated_at FROM oauth_accounts ORDER BY id DESC')->fetchAll();
    }

    public function findActive(string $provider): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM oauth_accounts WHERE provider = :provider AND status = "active" ORDER BY updated_at DESC LIMIT 1');
        $stmt->execute([':provider' => $provider]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $row['access_token'] = $this->crypto->decrypt((string) $row['access_token_enc']);
        $row['refresh_token'] = $this->crypto->decrypt((string) ($row['refresh_token_enc'] ?? ''));
        $row['scopes'] = json_decode((string) $row['scopes_json'], true) ?: [];
        return $row;
    }
}
