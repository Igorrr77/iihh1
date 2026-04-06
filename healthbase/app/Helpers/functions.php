<?php

declare(strict_types=1);

function root_path(string $path = ''): string
{
    $base = dirname(__DIR__, 2);
    return $path ? $base . '/' . ltrim($path, '/') : $base;
}

function config(string $file): array
{
    static $cache = [];
    if (!isset($cache[$file])) {
        $cache[$file] = require root_path('config/' . $file . '.php');
    }

    return $cache[$file];
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function app_base_path(): string
{
    $basePath = trim((string)(config('app')['base_path'] ?? ''));
    if ($basePath === '') {
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
        foreach (['/install', '/admin', '/cron', '/update', '/public'] as $suffix) {
            if ($dir === $suffix || str_ends_with($dir, $suffix)) {
                $dir = substr($dir, 0, -strlen($suffix));
                break;
            }
        }
        $basePath = $dir;
    }
    if ($basePath === '' || $basePath === '/') {
        return '';
    }

    return '/' . trim($basePath, '/');
}

function url(string $path = '/'): string
{
    $base = app_base_path();
    $normalized = '/' . ltrim($path, '/');
    if ($normalized === '//') {
        $normalized = '/';
    }

    return $base . ($normalized === '/' ? '/' : $normalized);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    $v = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $v;
}
