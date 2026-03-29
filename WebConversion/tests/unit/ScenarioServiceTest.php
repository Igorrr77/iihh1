<?php

declare(strict_types=1);

use App\Services\ScenarioService;

require_once __DIR__ . '/../bootstrap.php';

$service = new ScenarioService();

$ok = $service->importFromJson(json_encode([
    'events' => [
        ['at' => 0, 'type' => 'video_start'],
        ['at' => 10, 'type' => 'chat_message'],
    ],
], JSON_THROW_ON_ERROR));
assertTrue(($ok['ok'] ?? false) === true, 'Expected valid scenario JSON to pass');

$bad = $service->importFromJson('{"events":[{"at":"oops","type":"video_start"}]}');
assertTrue(($bad['ok'] ?? true) === false, 'Expected invalid at type to fail');

$badType = $service->validate(['events' => [['at' => 1, 'type' => 'unknown_type']]]);
assertTrue(($badType['ok'] ?? true) === false, 'Unsupported event type should fail');

$legacy = $service->importAdapter('legacy_v1', [
    'timeline' => [
        ['sec' => 15, 'kind' => 'chat_message', 'data' => ['text' => 'hi']],
    ],
]);
assertTrue(($legacy['schema_version'] ?? 0) === 2, 'Legacy adapter should migrate to schema v2');

$exportLegacy = $service->exportAdapter('legacy_v1', ['events' => [['at' => 5, 'type' => 'chat_message', 'payload' => []]]]);
assertTrue(isset($exportLegacy['timeline']), 'Legacy export should include timeline');

$template = $service->exportTemplate('wb_demo');
assertTrue(($template['webinar_id'] ?? '') === 'wb_demo', 'Expected export template webinar_id to match');
