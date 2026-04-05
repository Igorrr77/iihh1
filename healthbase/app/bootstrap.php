<?php

declare(strict_types=1);

session_start();

$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
    }
}

date_default_timezone_set('UTC');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = __DIR__ . '/' . $relative . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/Helpers/functions.php';

set_exception_handler(static function (\Throwable $e): void {
    $line = sprintf("[%s] %s in %s:%d\n", gmdate('c'), $e->getMessage(), $e->getFile(), $e->getLine());
    @file_put_contents(root_path('storage/logs/app.log'), $line, FILE_APPEND);

    if (!headers_sent()) {
        http_response_code(500);
    }

    if ((getenv('APP_DEBUG') ?: '0') === '1') {
        echo '<pre>' . e($e->getMessage()) . "\n" . e($e->getTraceAsString()) . '</pre>';
        return;
    }

    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/css/app.css"><title>Ошибка</title></head><body><main class="container"><h1>Временная техническая ошибка</h1><p>Мы уже записали ошибку в журнал. Попробуйте позже.</p></main></body></html>';
});
