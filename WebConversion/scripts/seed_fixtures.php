<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$pdo->exec("INSERT IGNORE INTO webinars (external_id, title, format, timezone, access_mode) VALUES ('fixture_wb', 'Fixture Webinar', 'auto', 'UTC', 'name_email')");

echo "Fixtures seeded\n";
