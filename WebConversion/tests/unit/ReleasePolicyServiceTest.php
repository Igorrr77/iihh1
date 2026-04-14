<?php

declare(strict_types=1);

use App\Services\ReleasePolicyService;

require_once __DIR__ . '/../bootstrap.php';

$service = new ReleasePolicyService();

$go = $service->evaluate(
    ['ga_ready' => true, 'critical_incidents_last_30d' => 0, 'all_stages_closed' => true, 'sla_registered' => true],
    ['overall_completion_percent' => 90, 'ga_ready' => true, 'critical_blockers' => []],
    ['chat_latency_p95_ms' => 800, 'payment_error_rate' => 0.01, 'runtime_error_rate' => 0.01]
);

assertTrue(($go['decision'] ?? 'no_go') === 'go', 'Expected GO decision for healthy gates and SLO');
assertTrue(($go['rollback_required'] ?? true) === false, 'Rollback must be false for healthy SLO');

$noGo = $service->evaluate(
    ['ga_ready' => true, 'critical_incidents_last_30d' => 0, 'all_stages_closed' => true, 'sla_registered' => true],
    ['overall_completion_percent' => 92, 'ga_ready' => true, 'critical_blockers' => []],
    ['chat_latency_p95_ms' => 1500, 'payment_error_rate' => 0.01, 'runtime_error_rate' => 0.01]
);

assertTrue(($noGo['decision'] ?? 'go') === 'no_go', 'Expected NO_GO when rollback trigger breaches');
assertTrue(($noGo['rollback_required'] ?? false) === true, 'Rollback must be true on SLO breach');
assertTrue(in_array('chat_latency_p95_ms', $noGo['rollback_triggers'] ?? [], true), 'chat_latency_p95_ms should trigger rollback');
