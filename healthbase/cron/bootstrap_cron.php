<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$envPath = root_path('.env');
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
    }
}

$token = $_GET['token'] ?? '';
if (!$token || $token !== getenv('CRON_TOKEN')) {
    http_response_code(403);
    exit('Forbidden');
}

$db = (new App\Core\Database(config('database')))->pdo();
$logger = new App\Services\Logger();
