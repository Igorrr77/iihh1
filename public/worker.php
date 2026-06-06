<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\ContentRepository;
use App\Core\Database;
use App\Core\Logger;
use App\Core\OAuthAccountRepository;
use App\Core\OAuthService;
use App\Core\OAuthStateRepository;
use App\Core\HttpClient;
use App\Core\Queue;
use App\Core\ScheduleRepository;
use App\Core\SchedulerService;
use App\Core\SourceRepository;
use App\Jobs\PullContentJob;

$pdo = Database::pdo();
$queue = new Queue($pdo);
$logger = new Logger($pdo);
$repo = new ContentRepository($pdo);
$jobHandler = new PullContentJob($repo, $logger);
$scheduler = new SchedulerService(new ScheduleRepository($pdo), new SourceRepository($pdo), $queue);
$oauth = new OAuthService(new HttpClient(), new OAuthStateRepository($pdo), new OAuthAccountRepository($pdo));

$iterations = PHP_SAPI === 'cli' ? 100 : 1;

for ($i = 0; $i < $iterations; $i++) {
    $scheduler->tick();
    foreach (['facebook','instagram','threads','x','tiktok','pinterest','reddit'] as $p) {
        $oauth->refreshIfNeeded($p);
    }

    $job = $queue->pop();
    if (!$job) {
        if (PHP_SAPI === 'cli') {
            usleep(200000);
            continue;
        }
        echo 'No jobs';
        exit;
    }

    try {
        if ($job['type'] === 'pull_content') {
            $jobHandler->handle($job['payload']);
            $queue->done((int) $job['id']);
            echo "done #{$job['id']}\n";
        } else {
            throw new RuntimeException('Unknown job type: ' . $job['type']);
        }
    } catch (Throwable $e) {
        $queue->fail($job, $e->getMessage());
        $logger->error('Job failed', ['job_id' => $job['id'], 'error' => $e->getMessage()]);
        echo "fail #{$job['id']}: {$e->getMessage()}\n";
    }
}
