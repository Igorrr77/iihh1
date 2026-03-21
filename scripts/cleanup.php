<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$db->query('DELETE FROM transcripts WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)');
$db->query('DELETE FROM analyses WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)');

echo "Cleanup done\n";
