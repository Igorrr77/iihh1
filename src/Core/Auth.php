<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function attempt(string $email, string $password): bool
    {
        self::start();

        $validEmail = Env::get('ADMIN_EMAIL', 'admin@example.com');
        $hash = Env::get('ADMIN_PASSWORD_HASH', '');

        if ($email !== $validEmail || $hash === '' || !password_verify($password, $hash)) {
            return false;
        }

        $_SESSION['auth'] = ['email' => $email, 'at' => time()];
        return true;
    }

    public static function check(): bool
    {
        self::start();
        return isset($_SESSION['auth']);
    }

    public static function require(): void
    {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }
}
