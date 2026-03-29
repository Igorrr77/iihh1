<?php

declare(strict_types=1);

use App\Services\GaStabilizationService;

require_once __DIR__ . '/../app/bootstrap.php';

$advisory = in_array('--advisory', $argv, true);

try {
    $report = (new GaStabilizationService())->buildFromSystem();
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

    $approved = (($report['ga_passport_status'] ?? 'blocked') === 'approved');
    if (!$approved && !$advisory) {
        exit(1);
    }
    exit(0);
} catch (Throwable $e) {
    echo json_encode([
        'generated_at' => gmdate(DATE_ATOM),
        'ga_passport_status' => 'blocked',
        'error' => $e->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

    if ($advisory) {
        exit(0);
    }

    exit(1);
}
