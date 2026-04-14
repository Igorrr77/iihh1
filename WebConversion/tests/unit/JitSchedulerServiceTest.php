<?php

declare(strict_types=1);

use App\Services\JitSchedulerService;

require_once __DIR__ . '/../bootstrap.php';

$service = new JitSchedulerService();
$instant = $service->calculateStartAt('instant', 'UTC');
$plus1 = $service->calculateStartAt('plus_1_min', 'UTC');

assertTrue($instant !== '', 'Instant start must be set');
assertTrue($plus1 !== '', 'Plus 1 min start must be set');

$fixed = $service->calculateStartAt('fixed', 'UTC', '2030-01-01 10:00:00');
assertTrue($fixed === '2030-01-01 10:00:00', 'Fixed mode should keep fixed UTC time');

$fromLocal = $service->calculateStartAt('fixed', 'Europe/Kyiv', null, '2030-01-01 12:00:00');
assertTrue($fromLocal !== '', 'Local fixed start should normalize to UTC');
