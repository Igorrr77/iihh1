<?php

declare(strict_types=1);

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    throw new RuntimeException('Root not found');
}

$backupDir = $root . '/storage/backups';
$files = glob($backupDir . '/*.sql') ?: [];
rsort($files);

if ($files === []) {
    echo "No backups found. Run php scripts/backup_db.php first.\n";
    exit(1);
}

$latest = $files[0];
$size = filesize($latest) ?: 0;
$hash = hash_file('sha256', $latest) ?: 'n/a';

echo "Latest backup: {$latest}\n";
echo "Size bytes: {$size}\n";
echo "SHA256: {$hash}\n";
echo "Restore command (manual): php scripts/restore_db.php {$latest}\n";
echo "Disaster drill check complete\n";
