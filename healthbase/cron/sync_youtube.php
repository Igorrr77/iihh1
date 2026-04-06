<?php

declare(strict_types=1);
require __DIR__ . '/bootstrap_cron.php';

$youtube = new App\Services\YouTubeService((string)getenv('YOUTUBE_API_KEY'), $logger);
$sync = new App\Sync\YouTubeSyncService($db, $youtube, $logger);
$result = $sync->run((string)getenv('YOUTUBE_CHANNEL_ID'));

echo json_encode(['ok' => true, 'result' => $result], JSON_UNESCAPED_UNICODE);
