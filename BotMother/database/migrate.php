<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/database.php';
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']),
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(255) UNIQUE, applied_at DATETIME NOT NULL)');
$applied = $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

foreach (glob(__DIR__ . '/migrations/*.sql') as $file) {
    $migration = basename($file);
    if (in_array($migration, $applied, true)) {
        continue;
    }
    $pdo->exec(file_get_contents($file));
    $stmt = $pdo->prepare('INSERT INTO migrations (migration, applied_at) VALUES (:migration, NOW())');
    $stmt->execute(['migration' => $migration]);
    echo "Applied: {$migration}\n";
}
