<?php

declare(strict_types=1);

use App\Models\GaGateRepository;

require_once __DIR__ . '/../app/bootstrap.php';

$advisory = in_array('--advisory', $argv, true);

try {
    $status = (new GaGateRepository())->gaGateStatus();
    $critical = (int) ($status['critical_incidents_last_30d'] ?? 999);
    $ok = $critical === 0;

    echo json_encode([
        'generated_at' => gmdate(DATE_ATOM),
        'critical_incidents_last_30d' => $critical,
        'stability_window_passed' => $ok,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

    if (!$ok && !$advisory) {
        exit(1);
    }
    exit(0);
} catch (Throwable $e) {
    echo json_encode([
        'generated_at' => gmdate(DATE_ATOM),
        'stability_window_passed' => false,
        'error' => $e->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

    if ($advisory) {
        exit(0);
    }

    exit(1);
}
