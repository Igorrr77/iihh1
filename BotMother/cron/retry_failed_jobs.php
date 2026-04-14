<?php

declare(strict_types=1);

require __DIR__ . '/../app/Core/Autoloader.php';
$loader = new App\Core\Autoloader(__DIR__ . '/../app');
$loader->register();

$db = (new App\Core\Database(require __DIR__ . '/../config/database.php'))->pdo();
$stmt = $db->query('SELECT * FROM job_queue WHERE status="failed" AND attempts < max_attempts ORDER BY id ASC LIMIT 100');

foreach ($stmt->fetchAll() as $job) {
    $db->prepare('UPDATE job_queue SET status="pending", available_at=DATE_ADD(NOW(), INTERVAL 1 MINUTE), updated_at=NOW() WHERE id=:id')
        ->execute(['id' => $job['id']]);
    echo "retried #{$job['id']}\n";
}
