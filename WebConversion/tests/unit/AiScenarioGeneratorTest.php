<?php

declare(strict_types=1);

use App\Services\AiScenarioGenerator;

require_once __DIR__ . '/../bootstrap.php';

$service = new AiScenarioGenerator();
$result = $service->generate(str_repeat('text ', 500), 350);

assertTrue(isset($result['meta']['virtual_viewers']) && (int) $result['meta']['virtual_viewers'] === 350, 'Expected viewers to propagate');
assertTrue(isset($result['events']) && is_array($result['events']) && count($result['events']) >= 3, 'Expected generated events');
assertTrue(($result['meta']['estimated_minutes'] ?? 0) >= 5, 'Expected estimated minutes >= 5');
