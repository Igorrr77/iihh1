<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AuthRepository
{
    public function findUserByEmail(string $email): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, email, password_hash, role FROM admin_users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findUserById(int $userId): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, email, role FROM admin_users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function issueTokenPair(int $userId, string $role): array
    {
        $accessToken = bin2hex(random_bytes(24));
        $refreshToken = bin2hex(random_bytes(32));

        $pdo = Database::connection();

        $accessStmt = $pdo->prepare(
            'INSERT INTO api_tokens (user_id, token, role, expires_at) VALUES (:user_id,:token,:role, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 12 HOUR))'
        );
        $accessStmt->execute(['user_id' => $userId, 'token' => $accessToken, 'role' => $role]);

        $refreshStmt = $pdo->prepare(
            'INSERT INTO auth_refresh_tokens (user_id, refresh_token, expires_at) VALUES (:user_id,:refresh_token, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY))'
        );
        $refreshStmt->execute(['user_id' => $userId, 'refresh_token' => $refreshToken]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    public function refreshFromToken(string $refreshToken): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT rt.id, rt.user_id, u.role
             FROM auth_refresh_tokens rt
             JOIN admin_users u ON u.id = rt.user_id
             WHERE rt.refresh_token = :refresh_token
               AND rt.revoked_at IS NULL
               AND rt.expires_at > UTC_TIMESTAMP()
             LIMIT 1'
        );
        $stmt->execute(['refresh_token' => $refreshToken]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }

        $revokeStmt = $pdo->prepare('UPDATE auth_refresh_tokens SET revoked_at = UTC_TIMESTAMP() WHERE id = :id');
        $revokeStmt->execute(['id' => (int) $row['id']]);

        $tokens = $this->issueTokenPair((int) $row['user_id'], (string) $row['role']);
        $tokens['user_id'] = (int) $row['user_id'];
        return $tokens;
    }

    public function revokeByAccessToken(string $token): void
    {
        $pdo = Database::connection();

        $tokenStmt = $pdo->prepare('SELECT user_id FROM api_tokens WHERE token = :token LIMIT 1');
        $tokenStmt->execute(['token' => $token]);
        $row = $tokenStmt->fetch();

        $revokeAccessStmt = $pdo->prepare('UPDATE api_tokens SET revoked_at = UTC_TIMESTAMP() WHERE token = :token');
        $revokeAccessStmt->execute(['token' => $token]);

        if (is_array($row)) {
            $this->revokeAllForUser((int) $row['user_id']);
        }
    }

    public function revokeAllForUser(int $userId): void
    {
        $pdo = Database::connection();
        $pdo->prepare('UPDATE api_tokens SET revoked_at = UTC_TIMESTAMP() WHERE user_id = :user_id AND revoked_at IS NULL')
            ->execute(['user_id' => $userId]);
        $pdo->prepare('UPDATE auth_refresh_tokens SET revoked_at = UTC_TIMESTAMP() WHERE user_id = :user_id AND revoked_at IS NULL')
            ->execute(['user_id' => $userId]);
    }

    public function listSessions(int $userId): array
    {
        $pdo = Database::connection();
        $access = $pdo->prepare(
            'SELECT "access" AS token_type, token AS token_id, expires_at, revoked_at, created_at
             FROM api_tokens
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT 50'
        );
        $access->execute(['user_id' => $userId]);

        $refresh = $pdo->prepare(
            'SELECT "refresh" AS token_type, refresh_token AS token_id, expires_at, revoked_at, created_at
             FROM auth_refresh_tokens
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT 50'
        );
        $refresh->execute(['user_id' => $userId]);

        return array_merge($access->fetchAll() ?: [], $refresh->fetchAll() ?: []);
    }

    public function findToken(string $token): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT t.id, t.user_id, t.role, t.expires_at, u.email
             FROM api_tokens t
             JOIN admin_users u ON u.id = t.user_id
             WHERE t.token = :token
               AND t.revoked_at IS NULL
               AND t.expires_at > UTC_TIMESTAMP()
             LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }
}
