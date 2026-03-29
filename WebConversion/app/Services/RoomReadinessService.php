<?php

declare(strict_types=1);

namespace App\Services;

final class RoomReadinessService
{
    /**
     * @return array<string, mixed>
     */
    public function buildSnapshot(): array
    {
        $blocks = [
            $this->buildBlock('A', 'Production Foundation', [
                ['item' => 'RBAC + auth sessions/revoke', 'status' => 'complete', 'critical' => true],
                ['item' => 'Versioned migrations', 'status' => 'complete', 'critical' => true],
                ['item' => 'Containerized runtime profile', 'status' => 'partial', 'critical' => false],
                ['item' => 'One-click CI/CD + rollback', 'status' => 'partial', 'critical' => true],
            ]),
            $this->buildBlock('B', 'Streaming Core & Room Runtime', [
                ['item' => 'Multi-provider video adapter', 'status' => 'complete', 'critical' => true],
                ['item' => 'Room runtime states (waiting/live/ended)', 'status' => 'complete', 'critical' => true],
                ['item' => 'Embeddable SDK + postMessage contract', 'status' => 'partial', 'critical' => true],
                ['item' => 'On-demand conversion parity checks', 'status' => 'partial', 'critical' => false],
            ]),
            $this->buildBlock('C', 'Scenario Engine & Visual Editor', [
                ['item' => 'Timeline/event engine', 'status' => 'complete', 'critical' => true],
                ['item' => 'Macro compiler + preview', 'status' => 'complete', 'critical' => true],
                ['item' => 'Version diff/rollback API', 'status' => 'complete', 'critical' => true],
                ['item' => 'Visual drag-drop editor UI', 'status' => 'missing', 'critical' => true],
            ]),
            $this->buildBlock('D', 'Chat, Moderation, AI Replies', [
                ['item' => 'Chat send/list/SSE stream', 'status' => 'complete', 'critical' => true],
                ['item' => 'Moderation + anti-spam', 'status' => 'complete', 'critical' => true],
                ['item' => 'AI reply policy', 'status' => 'complete', 'critical' => false],
                ['item' => 'Latency SLO instrumentation', 'status' => 'partial', 'critical' => false],
            ]),
            $this->buildBlock('E', 'Direct Response & Payments', [
                ['item' => 'Offer lifecycle API', 'status' => 'complete', 'critical' => true],
                ['item' => 'Checkout in room', 'status' => 'complete', 'critical' => true],
                ['item' => 'Multi-PSP full matrix', 'status' => 'partial', 'critical' => true],
                ['item' => 'Financial reconciliation trail', 'status' => 'complete', 'critical' => true],
            ]),
            $this->buildBlock('F', 'Marketing Automation', [
                ['item' => 'Segment engine', 'status' => 'complete', 'critical' => true],
                ['item' => 'Email cadence orchestrator', 'status' => 'complete', 'critical' => true],
                ['item' => 'Messenger channel execution', 'status' => 'partial', 'critical' => false],
                ['item' => 'CRM delivery with DLQ visibility', 'status' => 'partial', 'critical' => true],
            ]),
            $this->buildBlock('G', 'Analytics & ACE AI', [
                ['item' => 'Attribution + retention + export', 'status' => 'complete', 'critical' => true],
                ['item' => 'Insight readiness monitor', 'status' => 'complete', 'critical' => false],
                ['item' => 'ACE content generation', 'status' => 'complete', 'critical' => false],
                ['item' => 'Reproducible BI contracts', 'status' => 'partial', 'critical' => true],
            ]),
            $this->buildBlock('H', 'UI/UX Finalization', [
                ['item' => 'Responsive webinar room UX', 'status' => 'missing', 'critical' => true],
                ['item' => 'White-label theming', 'status' => 'missing', 'critical' => false],
                ['item' => 'Accessibility compliance baseline', 'status' => 'missing', 'critical' => true],
                ['item' => 'Mobile performance budget gates', 'status' => 'missing', 'critical' => false],
            ]),
        ];

        $score = 0.0;
        $maxScore = 0.0;
        $blockSummaries = [];
        $blockers = [];

        foreach ($blocks as $block) {
            $score += (float) $block['score_points'];
            $maxScore += (float) $block['max_points'];
            $blockSummaries[] = [
                'code' => $block['code'],
                'name' => $block['name'],
                'status' => $block['status'],
                'completion_percent' => $block['completion_percent'],
            ];

            foreach ($block['items'] as $item) {
                if (($item['critical'] ?? false) === true && $item['status'] !== 'complete') {
                    $blockers[] = [
                        'block_code' => $block['code'],
                        'block_name' => $block['name'],
                        'item' => $item['item'],
                        'status' => $item['status'],
                    ];
                }
            }
        }

        usort($blockers, static function (array $a, array $b): int {
            return strcmp((string) $a['block_code'], (string) $b['block_code']);
        });

        $overallPercent = $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0;
        $gaReady = $overallPercent >= 90 && $blockers === [];

        return [
            'generated_at' => gmdate(DATE_ATOM),
            'overall_completion_percent' => $overallPercent,
            'ga_ready' => $gaReady,
            'blocks' => $blockSummaries,
            'critical_blockers' => $blockers,
            'next_focus' => $this->buildNextFocus($blockers),
            'methodology' => [
                'weights' => ['complete' => 1.0, 'partial' => 0.5, 'missing' => 0.0],
                'ga_threshold_percent' => 90,
                'ga_requires_no_critical_blockers' => true,
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function buildBlock(string $code, string $name, array $items): array
    {
        $scoreMap = ['complete' => 1.0, 'partial' => 0.5, 'missing' => 0.0];
        $score = 0.0;

        foreach ($items as $item) {
            $score += $scoreMap[(string) ($item['status'] ?? 'missing')] ?? 0.0;
        }

        $maxPoints = (float) count($items);
        $completionPercent = $maxPoints > 0 ? (int) round(($score / $maxPoints) * 100) : 0;

        $status = 'missing';
        if ($completionPercent >= 85) {
            $status = 'complete';
        } elseif ($completionPercent > 0) {
            $status = 'partial';
        }

        return [
            'code' => $code,
            'name' => $name,
            'status' => $status,
            'completion_percent' => $completionPercent,
            'score_points' => $score,
            'max_points' => $maxPoints,
            'items' => $items,
        ];
    }

    /**
     * @param array<int, array<string, string>> $blockers
     * @return array<int, string>
     */
    private function buildNextFocus(array $blockers): array
    {
        $focus = [];
        foreach ($blockers as $blocker) {
            $focus[] = sprintf('[%s] %s', (string) ($blocker['block_code'] ?? '?'), (string) ($blocker['item'] ?? '')); 
            if (count($focus) === 3) {
                break;
            }
        }

        return $focus;
    }
}
