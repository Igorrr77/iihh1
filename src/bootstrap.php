<?php

declare(strict_types=1);

use App\Database;

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    throw new RuntimeException('Missing config/config.php. Copy config/config.example.php first.');
}

$config = require $configPath;

date_default_timezone_set('UTC');
session_name($config['app']['session_name']);
session_start();

$db = new Database($config['db']['dsn'], $config['db']['user'], $config['db']['password']);
