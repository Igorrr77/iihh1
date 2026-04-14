<?php

declare(strict_types=1);

$checks = [
    'php_8_2' => version_compare(PHP_VERSION, '8.2.0', '>='),
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'mbstring' => extension_loaded('mbstring'),
    'openssl' => extension_loaded('openssl'),
    'json' => extension_loaded('json'),
    'fileinfo' => extension_loaded('fileinfo'),
    'curl' => extension_loaded('curl'),
];

if (in_array(false, $checks, true)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'failed', 'checks' => $checks]);
    exit;
}

require __DIR__ . '/../database/migrate.php';

$dbConfig = require __DIR__ . '/../config/database.php';
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbConfig['host'], $dbConfig['port'], $dbConfig['database']),
    $dbConfig['username'],
    $dbConfig['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$seed = require __DIR__ . '/../database/seeds/system_seed.php';
$seed($pdo, $_POST);

$configLocal = "<?php\nreturn " . var_export([
    'installed_at' => date('c'),
    'db' => $dbConfig,
], true) . ";\n";
file_put_contents(__DIR__ . '/../config/config.local.php', $configLocal);

file_put_contents(__DIR__ . '/../storage/installed.lock', date('c'));
header('Content-Type: application/json');
echo json_encode(['status' => 'installed', 'checks' => $checks]);
