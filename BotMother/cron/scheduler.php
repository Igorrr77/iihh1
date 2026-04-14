<?php

declare(strict_types=1);

require __DIR__ . '/../app/Core/Autoloader.php';
$loader = new App\Core\Autoloader(__DIR__ . '/../app');
$loader->register();

$db = (new App\Core\Database(require __DIR__ . '/../config/database.php'))->pdo();
$stmt = $db->query('SELECT * FROM scheduled_jobs WHERE status="scheduled" AND run_at <= NOW() ORDER BY id ASC LIMIT 100');

foreach ($stmt->fetchAll() as $job) {
    $db->prepare('INSERT INTO job_queue (account_id, project_id, queue_name, job_type, payload_json, status, available_at, created_at, updated_at) VALUES (:account_id,:project_id,"default",:job_type,:payload_json,"pending",NOW(),NOW(),NOW())')
        ->execute([
            'account_id' => $job['account_id'],
            'project_id' => $job['project_id'],
            'job_type' => $job['job_type'],
            'payload_json' => $job['payload_json'],
        ]);
    $db->prepare('UPDATE scheduled_jobs SET status="queued", updated_at=NOW() WHERE id=:id')->execute(['id' => $job['id']]);
    echo "queued scheduled #{$job['id']}\n";
}
