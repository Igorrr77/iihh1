<?php

declare(strict_types=1);

$root = __DIR__;
$envPath = $root . '/.env';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function writeEnv(string $path, array $data): void
{
    $lines = [
        'APP_NAME=SocialHarvester',
        'APP_ENV=production',
        'APP_DEBUG=false',
        'APP_URL=' . $data['app_url'],
        'APP_KEY=' . $data['app_key'],
        'TZ=UTC',
        'DB_DRIVER=mysql',
        'DB_HOST=' . $data['db_host'],
        'DB_PORT=' . $data['db_port'],
        'DB_NAME=' . $data['db_name'],
        'DB_USER=' . $data['db_user'],
        'DB_PASS=' . $data['db_pass'],
        'ADMIN_EMAIL=' . $data['admin_email'],
        'ADMIN_PASSWORD_HASH=' . password_hash($data['admin_password'], PASSWORD_DEFAULT),
        'PROXY_POOL=' . $data['proxy_pool'],
        'USER_AGENT_POOL=' . $data['ua_pool'],
        // OAuth clients
        'FACEBOOK_CLIENT_ID=' . $data['facebook_client_id'],
        'FACEBOOK_CLIENT_SECRET=' . $data['facebook_client_secret'],
        'INSTAGRAM_CLIENT_ID=' . $data['instagram_client_id'],
        'INSTAGRAM_CLIENT_SECRET=' . $data['instagram_client_secret'],
        'THREADS_CLIENT_ID=' . $data['threads_client_id'],
        'THREADS_CLIENT_SECRET=' . $data['threads_client_secret'],
        'X_CLIENT_ID=' . $data['x_client_id'],
        'X_CLIENT_SECRET=' . $data['x_client_secret'],
        'TIKTOK_CLIENT_KEY=' . $data['tiktok_client_key'],
        'TIKTOK_CLIENT_SECRET=' . $data['tiktok_client_secret'],
        'PINTEREST_CLIENT_ID=' . $data['pinterest_client_id'],
        'PINTEREST_CLIENT_SECRET=' . $data['pinterest_client_secret'],
        'REDDIT_CLIENT_ID=' . $data['reddit_client_id'],
        'REDDIT_CLIENT_SECRET=' . $data['reddit_client_secret'],
        'REDDIT_USER_AGENT=' . $data['reddit_user_agent'],
        'TELEGRAM_BOT_TOKEN=' . $data['telegram_bot_token'],
    ];

    file_put_contents($path, implode("\n", $lines) . "\n");
}

function pdoMysql(array $data, bool $withDb = true): PDO
{
    $dbPart = $withDb ? ';dbname=' . $data['db_name'] : '';
    $dsn = sprintf('mysql:host=%s;port=%s%s;charset=utf8mb4', $data['db_host'], $data['db_port'], $dbPart);
    return new PDO($dsn, $data['db_user'], $data['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function ensureDatabase(array $data): void
{
    $pdo = pdoMysql($data, false);
    $dbName = str_replace('`', '``', $data['db_name']);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

function initDatabase(PDO $pdo): void
{
    $schema = [
        'CREATE TABLE IF NOT EXISTS sources (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            provider VARCHAR(32) NOT NULL,
            account_handle VARCHAR(190) NOT NULL,
            mode ENUM("topic","author") NOT NULL,
            query_text VARCHAR(255) DEFAULT NULL,
            content_types_json JSON NOT NULL,
            filters_json JSON NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uq_provider_handle_mode (provider, account_handle, mode)
        ) ENGINE=InnoDB',
        'CREATE TABLE IF NOT EXISTS schedules (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            source_id BIGINT UNSIGNED NOT NULL,
            cron_expr VARCHAR(64) NOT NULL,
            timezone VARCHAR(64) NOT NULL DEFAULT "UTC",
            next_run_at DATETIME NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT fk_schedules_source FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE CASCADE
        ) ENGINE=InnoDB',
        'CREATE TABLE IF NOT EXISTS content_items (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            source_id BIGINT UNSIGNED NULL,
            source VARCHAR(32) NOT NULL,
            content_type VARCHAR(32) NOT NULL,
            external_id VARCHAR(190) NOT NULL,
            author VARCHAR(190) DEFAULT NULL,
            title TEXT,
            body LONGTEXT,
            popularity_json JSON NOT NULL,
            media_json JSON NOT NULL,
            published_at DATETIME NULL,
            fetched_at DATETIME NOT NULL,
            raw_json LONGTEXT NOT NULL,
            UNIQUE KEY uq_source_external (source, external_id),
            KEY idx_source_type_published (source, content_type, published_at),
            CONSTRAINT fk_content_source FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE SET NULL
        ) ENGINE=InnoDB',
        'CREATE TABLE IF NOT EXISTS jobs (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            type VARCHAR(64) NOT NULL,
            payload LONGTEXT NOT NULL,
            status ENUM("queued","running","done","failed") NOT NULL DEFAULT "queued",
            attempts INT NOT NULL DEFAULT 0,
            max_attempts INT NOT NULL DEFAULT 5,
            available_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            last_error TEXT,
            KEY idx_jobs_status_available (status, available_at)
        ) ENGINE=InnoDB',
        'CREATE TABLE IF NOT EXISTS run_logs (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            level VARCHAR(16) NOT NULL,
            message TEXT NOT NULL,
            context_json LONGTEXT,
            created_at DATETIME NOT NULL,
            KEY idx_level_created (level, created_at)
        ) ENGINE=InnoDB',
        'CREATE TABLE IF NOT EXISTS oauth_states (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            provider VARCHAR(32) NOT NULL,
            state_token VARCHAR(190) NOT NULL,
            code_verifier VARCHAR(255) DEFAULT NULL,
            redirect_uri VARCHAR(255) NOT NULL,
            requested_scopes VARCHAR(1000) NOT NULL,
            created_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            UNIQUE KEY uq_state_token (state_token)
        ) ENGINE=InnoDB',
        'CREATE TABLE IF NOT EXISTS oauth_accounts (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            provider VARCHAR(32) NOT NULL,
            provider_user_id VARCHAR(190) NOT NULL,
            account_label VARCHAR(255) DEFAULT NULL,
            scopes_json JSON NOT NULL,
            access_token_enc LONGTEXT NOT NULL,
            refresh_token_enc LONGTEXT,
            token_type VARCHAR(50) DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL,
            refresh_expires_at DATETIME DEFAULT NULL,
            status ENUM("active","expired","revoked") NOT NULL DEFAULT "active",
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uq_provider_user (provider, provider_user_id)
        ) ENGINE=InnoDB',
    ];

    foreach ($schema as $sql) {
        $pdo->exec($sql);
    }
}

$errors = [];
$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'app_url' => trim($_POST['app_url'] ?? ''),
        'app_key' => trim($_POST['app_key'] ?? bin2hex(random_bytes(32))),
        'db_host' => trim($_POST['db_host'] ?? '127.0.0.1'),
        'db_port' => trim($_POST['db_port'] ?? '3306'),
        'db_name' => trim($_POST['db_name'] ?? ''),
        'db_user' => trim($_POST['db_user'] ?? ''),
        'db_pass' => trim($_POST['db_pass'] ?? ''),
        'admin_email' => trim($_POST['admin_email'] ?? ''),
        'admin_password' => trim($_POST['admin_password'] ?? ''),
        'proxy_pool' => trim($_POST['proxy_pool'] ?? ''),
        'ua_pool' => trim($_POST['ua_pool'] ?? ''),
        'telegram_bot_token' => trim($_POST['telegram_bot_token'] ?? ''),
        'reddit_client_id' => trim($_POST['reddit_client_id'] ?? ''),
        'reddit_client_secret' => trim($_POST['reddit_client_secret'] ?? ''),
        'reddit_user_agent' => trim($_POST['reddit_user_agent'] ?? 'SocialHarvester/1.0'),
        'facebook_client_id' => trim($_POST['facebook_client_id'] ?? ''),
        'facebook_client_secret' => trim($_POST['facebook_client_secret'] ?? ''),
        'instagram_client_id' => trim($_POST['instagram_client_id'] ?? ''),
        'instagram_client_secret' => trim($_POST['instagram_client_secret'] ?? ''),
        'threads_client_id' => trim($_POST['threads_client_id'] ?? ''),
        'threads_client_secret' => trim($_POST['threads_client_secret'] ?? ''),
        'x_client_id' => trim($_POST['x_client_id'] ?? ''),
        'x_client_secret' => trim($_POST['x_client_secret'] ?? ''),
        'tiktok_client_key' => trim($_POST['tiktok_client_key'] ?? ''),
        'tiktok_client_secret' => trim($_POST['tiktok_client_secret'] ?? ''),
        'pinterest_client_id' => trim($_POST['pinterest_client_id'] ?? ''),
        'pinterest_client_secret' => trim($_POST['pinterest_client_secret'] ?? ''),
    ];

    foreach (['app_url', 'db_name', 'db_user', 'admin_email', 'admin_password'] as $field) {
        if ($data[$field] === '') {
            $errors[] = "Поле {$field} обязательно";
        }
    }

    if ($errors === []) {
        try {
            ensureDatabase($data);
            $pdo = pdoMysql($data, true);
            initDatabase($pdo);
            writeEnv($envPath, $data);
            $done = true;
        } catch (Throwable $e) {
            $errors[] = 'Ошибка установки: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Install Social Harvester</title>
<style>body{font-family:Arial;background:#0f172a;color:#e2e8f0;margin:0}.wrap{max-width:920px;margin:28px auto;background:#111827;border-radius:12px;padding:20px}input,textarea{width:100%;padding:10px;margin:6px 0 12px;border:1px solid #334155;background:#1e293b;color:#fff;border-radius:8px;box-sizing:border-box}button{background:#2563eb;color:#fff;border:0;padding:10px 16px;border-radius:8px;cursor:pointer}.ok{background:#14532d;padding:12px;border-radius:8px;margin-bottom:12px}.err{background:#7f1d1d;padding:12px;border-radius:8px;margin-bottom:12px}</style></head>
<body><div class="wrap"><h1>Установка Social Harvester (MySQL + OAuth Vault)</h1>
<?php if ($done): ?><div class="ok">Готово. Удалите <b>install.php</b> и откройте <a style="color:#93c5fd" href="public/login.php">вход</a>.</div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="err"><?= h($error) ?></div><?php endforeach; ?>
<form method="post">
<h3>База данных / админ</h3>
<input name="app_url" placeholder="APP_URL https://example.com" required><input name="app_key" placeholder="APP_KEY (опционально)"><input name="db_host" placeholder="DB_HOST" value="127.0.0.1" required><input name="db_port" placeholder="DB_PORT" value="3306" required><input name="db_name" placeholder="DB_NAME" required><input name="db_user" placeholder="DB_USER" required><input name="db_pass" placeholder="DB_PASS" type="password"><input name="admin_email" placeholder="ADMIN_EMAIL" required><input name="admin_password" placeholder="ADMIN_PASSWORD" type="password" required>
<h3>Антиблокировка</h3>
<textarea name="proxy_pool" placeholder="PROXY_POOL (через запятую): http://user:pass@ip:port,..."></textarea><textarea name="ua_pool" placeholder="USER_AGENT_POOL (через ||)"></textarea>
<h3>OAuth client credentials (можно заполнить позже в .env)</h3>
<input name="facebook_client_id" placeholder="FACEBOOK_CLIENT_ID"><input name="facebook_client_secret" placeholder="FACEBOOK_CLIENT_SECRET"><input name="instagram_client_id" placeholder="INSTAGRAM_CLIENT_ID"><input name="instagram_client_secret" placeholder="INSTAGRAM_CLIENT_SECRET"><input name="threads_client_id" placeholder="THREADS_CLIENT_ID"><input name="threads_client_secret" placeholder="THREADS_CLIENT_SECRET"><input name="x_client_id" placeholder="X_CLIENT_ID"><input name="x_client_secret" placeholder="X_CLIENT_SECRET"><input name="tiktok_client_key" placeholder="TIKTOK_CLIENT_KEY"><input name="tiktok_client_secret" placeholder="TIKTOK_CLIENT_SECRET"><input name="pinterest_client_id" placeholder="PINTEREST_CLIENT_ID"><input name="pinterest_client_secret" placeholder="PINTEREST_CLIENT_SECRET"><input name="reddit_client_id" placeholder="REDDIT_CLIENT_ID"><input name="reddit_client_secret" placeholder="REDDIT_CLIENT_SECRET"><input name="reddit_user_agent" placeholder="REDDIT_USER_AGENT" value="SocialHarvester/1.0"><input name="telegram_bot_token" placeholder="TELEGRAM_BOT_TOKEN">
<button type="submit">Установить</button></form></div></body></html>
