<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GaGateRepository;

final class GaStabilizationService
{
    /**
     * @return array<string, mixed>
     */
    public function buildFromSystem(): array
    {
        $gaStatus = (new GaGateRepository())->gaGateStatus();
        $roomStatus = (new RoomReadinessService())->buildSnapshot();
        $policy = (new ReleasePolicyService())->evaluate($gaStatus, $roomStatus, []);
        $security = $this->securityPosture();

        return $this->buildPassport($gaStatus, $roomStatus, $policy, $security);
    }

    /**
     * @param array<string, mixed> $gaStatus
     * @param array<string, mixed> $roomStatus
     * @param array<string, mixed> $policy
     * @param array<string, mixed> $security
     * @return array<string, mixed>
     */
    public function buildPassport(array $gaStatus, array $roomStatus, array $policy, array $security): array
    {
        $critical30d = (int) ($gaStatus['critical_incidents_last_30d'] ?? 999);
        $gaReady = (bool) ($gaStatus['ga_ready'] ?? false);
        $roomReady = (bool) ($roomStatus['ga_ready'] ?? false);
        $policyDecision = (string) ($policy['decision'] ?? 'no_go');
        $securityReady = (bool) ($security['ready'] ?? false);

        $checks = [
            'critical_incidents_last_30d_zero' => $critical30d === 0,
            'ga_gate_ready' => $gaReady,
            'room_readiness_ready' => $roomReady,
            'policy_gate_go' => $policyDecision === 'go',
            'security_ready' => $securityReady,
        ];

        $allPassed = !in_array(false, $checks, true);

        return [
            'generated_at' => gmdate(DATE_ATOM),
            'ga_passport_status' => $allPassed ? 'approved' : 'blocked',
            'checks' => $checks,
            'ga_status' => $gaStatus,
            'room_status' => [
                'overall_completion_percent' => (int) ($roomStatus['overall_completion_percent'] ?? 0),
                'critical_blockers_count' => is_array($roomStatus['critical_blockers'] ?? null) ? count($roomStatus['critical_blockers']) : 0,
            ],
            'policy' => [
                'decision' => $policyDecision,
                'rollback_required' => (bool) ($policy['rollback_required'] ?? false),
                'reasons' => $policy['reasons'] ?? [],
            ],
            'security' => $security,
            'recommendation' => $allPassed ? 'Proceed with GA release window.' : 'Do not release. Resolve blocking checks first.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function securityPosture(): array
    {
        $appDebug = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL);
        $webhookSecret = (string) (getenv('PAYMENT_WEBHOOK_SECRET') ?: getenv('PAYMENT_WEBHOOK_SECRETS'));
        $embedSecret = (string) getenv('EMBED_TOKEN_SECRET');

        $checks = [
            'app_debug_disabled' => $appDebug === false,
            'payment_webhook_secret_set' => $webhookSecret !== '',
            'embed_token_secret_set' => $embedSecret !== '',
        ];

        return [
            'ready' => !in_array(false, $checks, true),
            'checks' => $checks,
        ];
    }
}
