<?php

declare(strict_types=1);

require __DIR__ . '/../app/Core/Autoloader.php';
$loader = new App\Core\Autoloader(__DIR__ . '/../app');
$loader->register();

$db = (new App\Core\Database(require __DIR__ . '/../config/database.php'))->pdo();
$today = date('Y-m-d');
$entries = (int)$db->query('SELECT COUNT(*) FROM funnel_entries WHERE DATE(created_at) = CURDATE()')->fetchColumn();
$db->prepare('INSERT INTO daily_stats (account_id, project_id, bot_id, stat_date, metric_code, metric_value, created_at) VALUES (1,1,NULL,:stat_date,"funnel_entries",:metric_value,NOW()) ON DUPLICATE KEY UPDATE metric_value=:metric_value')
    ->execute(['stat_date' => $today, 'metric_value' => $entries]);

echo "stats aggregated\n";
