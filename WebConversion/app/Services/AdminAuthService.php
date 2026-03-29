<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Response;

final class AdminAuthService
{
    public function requireAdmin(): void
    {
        $expected = getenv('ADMIN_API_KEY') ?: '';
        if ($expected === '') {
            Response::json(['error' => 'ADMIN_API_KEY не настроен'], 500);
            exit;
        }

        $provided = $this->readApiKey();
        if (!hash_equals($expected, $provided)) {
            Response::json(['error' => 'Требуется авторизация администратора'], 401);
            exit;
        }
    }

    private function readApiKey(): string
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (strtolower((string) $key) === 'x-api-key') {
                    return (string) $value;
                }
            }
        }

        return (string) ($_SERVER['HTTP_X_API_KEY'] ?? '');
    }
}
