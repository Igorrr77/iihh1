<?php

declare(strict_types=1);
require __DIR__ . '/bootstrap_cron.php';

foreach (glob(root_path('storage/cache/*.json')) ?: [] as $file) {
    @unlink($file);
}
$logger->log('app', 'Cache rebuilt via cron');
echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
