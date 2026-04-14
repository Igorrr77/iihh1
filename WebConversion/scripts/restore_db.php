<?php

declare(strict_types=1);

$file = $argv[1] ?? null;
if (!is_string($file) || $file === '' || !is_file($file)) {
    echo "Usage: php scripts/restore_db.php <backup.sql>\n";
    exit(1);
}

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    throw new RuntimeException('Root not found');
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

$passwordPart = $dbPass !== '' ? ' -p' . escapeshellarg($dbPass) : '';
$cmd = 'mysql -h' . escapeshellarg($dbHost) . ' -u' . escapeshellarg($dbUser) . $passwordPart . ' ' . escapeshellarg($dbName) . ' < ' . escapeshellarg($file);

exec($cmd, $out, $code);
if ($code !== 0) {
    echo "Restore failed\n";
    exit(1);
}

echo "Restore completed from: {$file}\n";
