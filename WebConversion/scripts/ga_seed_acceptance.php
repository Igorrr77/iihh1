<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\GaGateRepository;

$repo = new GaGateRepository();
foreach (range('A', 'H') as $stage) {
    $repo->setStage($stage, 'Stage ' . $stage, true, 'release-owner', 'seeded completion marker');
}

$repo->upsertSla('availability', '99.9%', 'oncall@webconversion', 'https://dash.example/availability', 'https://wiki.example/runbook');
$repo->addGoNoGo('go', 'release-board', 'Seeded go decision for automation env');

echo "GA acceptance seed applied\n";
