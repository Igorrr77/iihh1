<?php

declare(strict_types=1);

use App\Services\ScenarioMacroCompiler;

require_once __DIR__ . '/../bootstrap.php';

$service = new ScenarioMacroCompiler();

$compiled = $service->compile([
    'events' => [
        ['at' => 5, 'type' => 'macro_repeat_message', 'payload' => ['times' => 3, 'step_sec' => 10, 'text' => 'Hi']],
    ],
]);

assertTrue(count($compiled['events']) === 3, 'Macro must expand into 3 events');
assertTrue((int) $compiled['events'][1]['at'] === 15, 'Second event timestamp must be 15');

$diff = $service->diff(['events' => [1,2]], ['events' => [1,2,3,4]]);
assertTrue((int) $diff['delta'] === 2, 'Diff delta must be 2');
