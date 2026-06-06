<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\ExportService;

Auth::require();
$pdo = Database::pdo();
$export = new ExportService($pdo);
$export->streamCsv([
    'provider' => trim($_GET['provider'] ?? ''),
    'content_type' => trim($_GET['content_type'] ?? ''),
    'q' => trim($_GET['q'] ?? ''),
]);
