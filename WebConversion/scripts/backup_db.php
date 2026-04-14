<?php

declare(strict_types=1);

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    throw new RuntimeException('Root not found');
}

$backupDir = $root . '/storage/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
    throw new RuntimeException('Cannot create backup directory');
}

$envPath = $root . '/.env';
$vars = [];
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $vars[trim($k)] = trim($v);
    }
}

$dbName = $vars['DB_NAME'] ?? 'webconversion';
$dbHost = $vars['DB_HOST'] ?? '127.0.0.1';
$dbUser = $vars['DB_USER'] ?? 'root';
$dbPass = $vars['DB_PASS'] ?? '';

$file = $backupDir . '/backup_' . gmdate('Ymd_His') . '.sql';
$passwordPart = $dbPass !== '' ? ' -p' . escapeshellarg($dbPass) : '';
$cmd = 'mysqldump -h' . escapeshellarg($dbHost) . ' -u' . escapeshellarg($dbUser) . $passwordPart . ' ' . escapeshellarg($dbName) . ' > ' . escapeshellarg($file);

exec($cmd, $out, $code);
if ($code !== 0) {
    echo "Backup failed\n";
    exit(1);
}

echo "Backup created: {$file}\n";
