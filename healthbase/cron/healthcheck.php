<?php

declare(strict_types=1);
require __DIR__ . '/bootstrap_cron.php';

$checks = [
    'db' => true,
    'storage_writable' => is_writable(root_path('storage')),
    'last_sync' => $db->query('SELECT MAX(finished_at) FROM sync_runs')->fetchColumn(),
    'queued_ai_jobs' => (int)$db->query("SELECT COUNT(*) FROM ai_jobs WHERE status='queued'")->fetchColumn(),
];
echo json_encode(['ok' => true, 'checks' => $checks], JSON_UNESCAPED_UNICODE);
