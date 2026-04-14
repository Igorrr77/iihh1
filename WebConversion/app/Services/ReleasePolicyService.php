<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GaGateRepository;

final class ReleasePolicyService
{
    /**
     * @param array<string, mixed> $sloMetrics
     * @return array<string, mixed>
     */
    public function buildFromRepositories(array $sloMetrics = []): array
    {
        $gaStatus = (new GaGateRepository())->gaGateStatus();
        $roomStatus = (new RoomReadinessService())->buildSnapshot();

        return $this->evaluate($gaStatus, $roomStatus, $sloMetrics);
    }

    /**
     * @param array<string, mixed> $gaStatus
     * @param array<string, mixed> $roomStatus
     * @param array<string, mixed> $sloMetrics
     * @return array<string, mixed>
     */
    public function evaluate(array $gaStatus, array $roomStatus, array $sloMetrics): array
    {
        $roomPercent = (int) ($roomStatus['overall_completion_percent'] ?? 0);
        $roomHasCriticalBlockers = ($roomStatus['critical_blockers'] ?? []) !== [];
        $gaReady = (bool) ($gaStatus['ga_ready'] ?? false);

        $sloPolicy = [
            'chat_latency_p95_ms' => ['max' => 1000.0, 'rollback_on_breach' => true],
            'payment_error_rate' => ['max' => 0.03, 'rollback_on_breach' => true],
            'runtime_error_rate' => ['max' => 0.02, 'rollback_on_breach' => true],
        ];

        $sloChecks = [];
        $rollbackTriggers = [];

        foreach ($sloPolicy as $key => $policy) {
            if (!array_key_exists($key, $sloMetrics)) {
                $sloChecks[$key] = ['status' => 'missing', 'threshold_max' => $policy['max']];
                continue;
            }

            $value = (float) $sloMetrics[$key];
            $breach = $value > (float) $policy['max'];
            $sloChecks[$key] = [
                'status' => $breach ? 'breach' : 'ok',
                'value' => $value,
                'threshold_max' => $policy['max'],
            ];

            if ($breach && (($policy['rollback_on_breach'] ?? false) === true)) {
                $rollbackTriggers[] = $key;
            }
        }

        $decision = 'go';
        $reasons = [];

        if ($gaReady !== true) {
            $decision = 'no_go';
            $reasons[] = 'GA gate status is not ready';
        }

        if ($roomPercent < 85 || $roomHasCriticalBlockers) {
            $decision = 'no_go';
            $reasons[] = 'Room readiness policy not satisfied';
        }

        if ($rollbackTriggers !== []) {
            $decision = 'no_go';
            $reasons[] = 'SLO rollback triggers breached';
        }

        return [
            'generated_at' => gmdate(DATE_ATOM),
            'decision' => $decision,
            'reasons' => $reasons,
            'rollback_required' => $rollbackTriggers !== [],
            'rollback_triggers' => $rollbackTriggers,
            'ga_gate' => [
                'ga_ready' => $gaReady,
                'critical_incidents_last_30d' => (int) ($gaStatus['critical_incidents_last_30d'] ?? 0),
                'all_stages_closed' => (bool) ($gaStatus['all_stages_closed'] ?? false),
                'sla_registered' => (bool) ($gaStatus['sla_registered'] ?? false),
            ],
            'room_readiness' => [
                'overall_completion_percent' => $roomPercent,
                'ga_ready' => (bool) ($roomStatus['ga_ready'] ?? false),
                'critical_blockers_count' => is_array($roomStatus['critical_blockers'] ?? null)
                    ? count($roomStatus['critical_blockers'])
                    : 0,
            ],
            'slo_checks' => $sloChecks,
        ];
    }
}
