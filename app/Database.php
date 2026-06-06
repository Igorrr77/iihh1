<?php

declare(strict_types=1);

namespace Commentor;

use PDO;

final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dbPath = Env::get('DB_PATH', dirname(__DIR__) . '/storage/commentor.sqlite');
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        self::runMigrations($pdo);

        self::$instance = $pdo;
        return $pdo;
    }

    private static function runMigrations(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS logs (id INTEGER PRIMARY KEY AUTOINCREMENT, level TEXT NOT NULL, message TEXT NOT NULL, payload_json TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');

        $columns = $pdo->query("PRAGMA table_info(accounts)")->fetchAll();
        $accountColumnNames = array_column($columns, 'name');
        if ($accountColumnNames !== []) {
            if (!in_array('token_expires_at', $accountColumnNames, true)) {
                $pdo->exec('ALTER TABLE accounts ADD COLUMN token_expires_at INTEGER');
            }
        }

        $commentCols = $pdo->query("PRAGMA table_info(comments)")->fetchAll();
        $commentNames = array_column($commentCols, 'name');
        if ($commentNames !== []) {
            if (!in_array('attempts', $commentNames, true)) {
                $pdo->exec('ALTER TABLE comments ADD COLUMN attempts INTEGER NOT NULL DEFAULT 0');
            }
            if (!in_array('next_attempt_at', $commentNames, true)) {
                $pdo->exec('ALTER TABLE comments ADD COLUMN next_attempt_at TEXT');
            }
            if (!in_array('locked_at', $commentNames, true)) {
                $pdo->exec('ALTER TABLE comments ADD COLUMN locked_at TEXT');
            }
            $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_comments_unique_external ON comments(account_id, external_comment_id)');
        }
    }
}
