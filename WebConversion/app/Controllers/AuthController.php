<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\AuditLogRepository;
use App\Models\AuthRepository;
use App\Services\RbacAuthService;

final class AuthController
{
    public function login(): void
    {
        $payload = $this->readJsonBody();
        $email = (string) ($payload['email'] ?? '');
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            Response::json(['error' => 'email и password обязательны'], 422);
            return;
        }

        $repo = new AuthRepository();
        $user = $repo->findUserByEmail($email);
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            Response::json(['error' => 'Неверные учетные данные'], 401);
            return;
        }

        $tokens = $repo->issueTokenPair((int) $user['id'], (string) $user['role']);
        (new AuditLogRepository())->write('auth:' . $email, 'auth_login', ['user_id' => (int) $user['id']]);

        Response::json([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'role' => $user['role'],
        ]);
    }

    public function refresh(): void
    {
        $payload = $this->readJsonBody();
        $refreshToken = (string) ($payload['refresh_token'] ?? '');
        if ($refreshToken === '') {
            Response::json(['error' => 'refresh_token обязателен'], 422);
            return;
        }

        $tokens = (new AuthRepository())->refreshFromToken($refreshToken);
        if ($tokens === null) {
            Response::json(['error' => 'Недействительный refresh token'], 401);
            return;
        }

        (new AuditLogRepository())->write('auth:user:' . (string) $tokens['user_id'], 'auth_refresh_rotated', []);
        Response::json([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
        ]);
    }

    public function logout(): void
    {
        $token = $this->readToken();
        if ($token === '') {
            Response::json(['error' => 'Auth token required'], 401);
            return;
        }

        (new AuthRepository())->revokeByAccessToken($token);
        (new AuditLogRepository())->write('auth:token', 'auth_logout', []);
        Response::json(['ok' => true]);
    }

    public function sessions(): void
    {
        $tokenRecord = (new RbacAuthService())->requireRole(['owner', 'admin', 'moderator', 'sales']);
        $sessions = (new AuthRepository())->listSessions((int) $tokenRecord['user_id']);
        Response::json(['sessions' => $sessions]);
    }

    public function revokeAll(): void
    {
        $tokenRecord = (new RbacAuthService())->requireRole(['owner', 'admin', 'moderator', 'sales']);
        (new AuthRepository())->revokeAllForUser((int) $tokenRecord['user_id']);
        (new AuditLogRepository())->write('auth:user:' . (string) $tokenRecord['user_id'], 'auth_revoke_all', []);
        Response::json(['ok' => true]);
    }

    public function me(): void
    {
        $tokenRecord = (new RbacAuthService())->requireRole(['owner', 'admin', 'moderator', 'sales']);
        Response::json([
            'user_id' => $tokenRecord['user_id'],
            'email' => $tokenRecord['email'],
            'role' => $tokenRecord['role'],
            'expires_at' => $tokenRecord['expires_at'],
        ]);
    }

    private function readToken(): string
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (is_array($headers)) {
            foreach ($headers as $k => $v) {
                if (strtolower((string) $k) === 'x-auth-token') {
                    return (string) $v;
                }
            }
        }

        return (string) ($_SERVER['HTTP_X_AUTH_TOKEN'] ?? '');
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
