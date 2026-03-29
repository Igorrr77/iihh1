<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Response;
use App\Models\AuthRepository;

final class RbacAuthService
{
    public function requireRole(array $roles): array
    {
        $token = $this->readToken();
        if ($token === '') {
            Response::json(['error' => 'Auth token required'], 401);
            exit;
        }

        $record = (new AuthRepository())->findToken($token);
        if ($record === null) {
            Response::json(['error' => 'Invalid auth token'], 401);
            exit;
        }

        if (!in_array((string) $record['role'], $roles, true)) {
            Response::json(['error' => 'Forbidden for this role'], 403);
            exit;
        }

        return $record;
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
}
