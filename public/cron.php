<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use Commentor\CommentProcessor;
use Commentor\Database;
use Commentor\Env;
use Commentor\GeminiClient;
use Commentor\Logger;
use Commentor\PlatformClient;
use Commentor\SettingRepository;

$secret = $_GET['secret'] ?? '';
if ($secret !== Env::get('CRON_SHARED_SECRET', '')) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$pdo = Database::connection();
$logger = new Logger($pdo);
$settings = new SettingRepository($pdo);
$gemini = new GeminiClient(
    Env::get('GEMINI_API_KEY', ''),
    Env::get('GEMINI_MODEL', 'gemini-3.1-flash-lite-preview')
);
$platformClient = new PlatformClient($pdo, $logger);
$processor = new CommentProcessor($pdo, $settings, $gemini, $platformClient, $logger);
$processed = $processor->processPending(20);
$logger->info('Cron processed batch', ['count' => $processed]);

echo 'Processed: ' . $processed;
