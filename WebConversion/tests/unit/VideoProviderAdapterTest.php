<?php

declare(strict_types=1);

use App\Services\VideoProviderAdapter;

require_once __DIR__ . '/../bootstrap.php';

$service = new VideoProviderAdapter();
$res = $service->resolvePlayback('youtube', 'abc123');
assertTrue(str_contains((string) $res['url'], 'abc123'), 'YouTube URL should contain external id');
assertTrue(($res['quality'] ?? '') === '1080p', 'Expected 1080p quality');
