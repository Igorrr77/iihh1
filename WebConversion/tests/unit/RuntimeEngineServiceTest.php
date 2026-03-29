<?php

declare(strict_types=1);

use App\Services\RuntimeEngineService;

require_once __DIR__ . '/../bootstrap.php';

$service = new RuntimeEngineService();
$events = [
    ['at' => 0, 'type' => 'video_start'],
    ['at' => 10, 'type' => 'chat_message'],
    ['at' => 20, 'type' => 'offer_popup'],
];

$due = $service->dueEvents($events, 10);
assertTrue(count($due) === 2, 'At elapsed=10 exactly 2 events must be due');
