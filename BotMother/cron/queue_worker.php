<?php

declare(strict_types=1);

$app = require __DIR__ . '/../bootstrap.php';
$db = (new App\Core\Database(require __DIR__ . '/../config/database.php'))->pdo();

$stmt = $db->query('SELECT * FROM job_queue WHERE status="pending" AND available_at <= NOW() ORDER BY id ASC LIMIT 20');
$jobs = $stmt->fetchAll();

foreach ($jobs as $job) {
    $db->prepare('UPDATE job_queue SET status="running", reserved_at=NOW(), attempts=attempts+1, updated_at=NOW() WHERE id=:id')->execute(['id' => $job['id']]);
    try {
        // foundation handler
        $db->prepare('UPDATE job_queue SET status="completed", completed_at=NOW(), updated_at=NOW() WHERE id=:id')->execute(['id' => $job['id']]);
        echo "completed job #{$job['id']}\n";
    } catch (Throwable $e) {
        $db->prepare('UPDATE job_queue SET status="failed", failed_at=NOW(), last_error=:err, updated_at=NOW() WHERE id=:id')->execute(['id' => $job['id'], 'err' => $e->getMessage()]);
        $db->prepare('INSERT INTO failed_jobs (job_queue_id, job_type, payload_json, attempts, error_message, failed_at) VALUES (:job_queue_id,:job_type,:payload_json,:attempts,:error_message,NOW())')
            ->execute(['job_queue_id' => $job['id'], 'job_type' => $job['job_type'], 'payload_json' => $job['payload_json'], 'attempts' => $job['attempts'] + 1, 'error_message' => $e->getMessage()]);
    }
}
