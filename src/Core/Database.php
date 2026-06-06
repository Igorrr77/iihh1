<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    public static function pdo(): PDO
    {
        $driver = Env::get('DB_DRIVER', 'mysql');

        if ($driver === 'sqlite') {
            $dsn = Env::get('DB_DSN', 'sqlite:' . dirname(__DIR__, 2) . '/storage/database.sqlite');
            $pdo = new PDO($dsn ?: '');
        } else {
            $host = Env::get('DB_HOST', '127.0.0.1');
            $port = Env::get('DB_PORT', '3306');
            $db = Env::get('DB_NAME', 'social_harvester');
            $user = Env::get('DB_USER', 'root');
            $pass = Env::get('DB_PASS', '');
            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            $pdo = new PDO($dsn, $user ?: '', $pass ?: '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }
}
