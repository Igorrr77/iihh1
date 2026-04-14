<?php

declare(strict_types=1);

use App\Services\ReleasePolicyService;

require_once __DIR__ . '/../app/bootstrap.php';

$advisory = in_array('--advisory', $argv, true);

$slo = [
    'chat_latency_p95_ms' => getenv('SLO_CHAT_P95_MS') ?: null,
    'payment_error_rate' => getenv('SLO_PAYMENT_ERROR_RATE') ?: null,
    'runtime_error_rate' => getenv('SLO_RUNTIME_ERROR_RATE') ?: null,
];
$slo = array_filter($slo, static fn ($v): bool => $v !== null && $v !== '');

try {
    $report = (new ReleasePolicyService())->buildFromRepositories($slo);
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

    if (($report['decision'] ?? 'no_go') !== 'go' && !$advisory) {
        exit(1);
    }

    exit(0);
} catch (Throwable $e) {
    $fallback = [
        'generated_at' => gmdate(DATE_ATOM),
        'decision' => 'no_go',
        'error' => $e->getMessage(),
    ];
    echo json_encode($fallback, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

    if ($advisory) {
        exit(0);
    }

    exit(1);
}
