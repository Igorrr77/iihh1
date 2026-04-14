<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$dir = __DIR__ . '/../database/migrations';
if (!is_dir($dir)) {
    throw new RuntimeException('Migrations directory not found');
}

$files = glob($dir . '/*.sql');
if ($files === false) {
    throw new RuntimeException('Unable to read migrations directory');
}

sort($files);

$pdo = Database::connection();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(255) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');

$applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN) ?: [];
$appliedMap = array_fill_keys(array_map('strval', $applied), true);

$appliedCount = 0;

foreach ($files as $file) {
    $version = basename($file);
    if (isset($appliedMap[$version])) {
        continue;
    }

    $sql = file_get_contents($file);
    if (!is_string($sql) || trim($sql) === '') {
        throw new RuntimeException('Migration SQL is empty: ' . $version);
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (:version)');
        $stmt->execute(['version' => $version]);
        $pdo->commit();
        $appliedCount++;
        echo "Applied migration: {$version}\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo "Migration failed ({$version}): " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "Migrations completed. Newly applied: {$appliedCount}\n";
