<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\GaGateRepository;

$status = (new GaGateRepository())->gaGateStatus();

echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

if (($status['ga_ready'] ?? false) !== true) {
    exit(1);
}
