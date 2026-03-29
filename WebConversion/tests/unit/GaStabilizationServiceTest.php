<?php

declare(strict_types=1);

use App\Services\GaStabilizationService;

require_once __DIR__ . '/../bootstrap.php';

$service = new GaStabilizationService();

$approved = $service->buildPassport(
    ['ga_ready' => true, 'critical_incidents_last_30d' => 0],
    ['ga_ready' => true, 'overall_completion_percent' => 92, 'critical_blockers' => []],
    ['decision' => 'go', 'rollback_required' => false, 'reasons' => []],
    ['ready' => true, 'checks' => ['app_debug_disabled' => true]]
);
assertTrue(($approved['ga_passport_status'] ?? 'blocked') === 'approved', 'Passport should be approved when all checks pass');

$blocked = $service->buildPassport(
    ['ga_ready' => false, 'critical_incidents_last_30d' => 2],
    ['ga_ready' => false, 'overall_completion_percent' => 70, 'critical_blockers' => [['x' => 1]]],
    ['decision' => 'no_go', 'rollback_required' => true, 'reasons' => ['x']],
    ['ready' => false, 'checks' => ['app_debug_disabled' => false]]
);
assertTrue(($blocked['ga_passport_status'] ?? 'approved') === 'blocked', 'Passport should be blocked on failed checks');
