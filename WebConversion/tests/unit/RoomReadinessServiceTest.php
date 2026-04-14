<?php

declare(strict_types=1);

use App\Services\RoomReadinessService;

require_once __DIR__ . '/../bootstrap.php';

$service = new RoomReadinessService();
$snapshot = $service->buildSnapshot();

assertTrue(isset($snapshot['overall_completion_percent']), 'Overall completion must exist');
assertTrue((int) $snapshot['overall_completion_percent'] > 0, 'Overall completion should be greater than zero');
assertTrue(($snapshot['ga_ready'] ?? true) === false, 'GA readiness should be false with critical blockers');

$blocks = $snapshot['blocks'] ?? [];
assertTrue(is_array($blocks) && count($blocks) === 8, 'Must include exactly 8 roadmap blocks');

$codes = array_map(static fn (array $block): string => (string) ($block['code'] ?? ''), $blocks);
assertTrue($codes === ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'], 'Block codes must be ordered from A to H');

$blockers = $snapshot['critical_blockers'] ?? [];
assertTrue(is_array($blockers) && count($blockers) >= 1, 'Must expose critical blockers');
assertTrue((string) ($blockers[0]['block_code'] ?? '') === 'A', 'Blockers should be sorted by block code');

$focus = $snapshot['next_focus'] ?? [];
assertTrue(is_array($focus) && count($focus) === 3, 'Next focus should provide first three priorities');
