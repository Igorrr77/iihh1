<?php

declare(strict_types=1);

use App\Services\ChatModerationService;

require_once __DIR__ . '/../bootstrap.php';

$service = new ChatModerationService();
assertTrue($service->isAllowed('Привет, как дела?'), 'Normal message should be allowed');
assertTrue(!$service->isAllowed('THIS IS SCAM SCAM SCAM'), 'Blocked words / caps abuse should be denied');
assertTrue(!$service->isAllowed('https://a.com https://b.com https://c.com'), 'Too many links should be denied');
