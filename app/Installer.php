<?php

declare(strict_types=1);

namespace Commentor;

use PDO;

final class Installer
{
    public static function run(string $adminUser, string $adminPassword, string $geminiApiKey): void
    {
        $root = dirname(__DIR__);
        $envPath = $root . '/.env';
        $dbPath = $root . '/storage/commentor.sqlite';

        if (!is_dir($root . '/storage')) {
            mkdir($root . '/storage', 0775, true);
        }

        $encryptionKey = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

        $envContent = [
            'APP_NAME=Commentor',
            'APP_ENV=production',
            'APP_URL=',
            'DB_PATH=' . $dbPath,
            'ADMIN_USER=' . $adminUser,
            'ADMIN_PASSWORD_HASH=' . password_hash($adminPassword, PASSWORD_DEFAULT),
            'APP_ENCRYPTION_KEY=' . $encryptionKey,
            'GEMINI_MODEL=gemini-3.1-flash-lite-preview',
            'GEMINI_API_KEY=' . $geminiApiKey,
            'DEFAULT_CTA_LINK=https://028.uno/diag',
            'RESPONSE_DEADLINE_SECONDS=180',
            'MAX_RETRY_ATTEMPTS=5',
            'RETRY_BASE_SECONDS=30',
            'CRON_SHARED_SECRET=' . bin2hex(random_bytes(20)),
            'WEBHOOK_SHARED_SECRET=' . bin2hex(random_bytes(20)),
            'WEBHOOK_VERIFY_TOKEN=' . bin2hex(random_bytes(16)),
            'META_GRAPH_VERSION=v22.0',
            'META_APP_SECRET=',
        ];

        file_put_contents($envPath, implode(PHP_EOL, $envContent) . PHP_EOL);

        Env::load($envPath);
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $schema = file_get_contents(__DIR__ . '/schema.sql');
        if ($schema !== false) {
            $pdo->exec($schema);
        }

        $insertSetting = $pdo->prepare('INSERT INTO settings (key, value) VALUES (:key, :value)');
        $defaults = [
            'system_prompt' => 'Ты мягкий эксперт в сфере превентивного и восстановительного здоровья. Отвечай с высокой эмпатией и уважением. Не давай детальные персонализированные назначения или индивидуальные схемы лечения в комментарии. Давай только общие безопасные подходы и объясняй, что персонализированный маршрут формируется на детальной консультации-диагностике.',
            'cta_link' => 'https://028.uno/diag',
            'response_language' => 'ru',
        ];

        foreach ($defaults as $key => $value) {
            $insertSetting->execute([':key' => $key, ':value' => $value]);
        }
    }
}
