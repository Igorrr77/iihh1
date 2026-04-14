<?php

declare(strict_types=1);

require __DIR__ . '/../app/Core/Autoloader.php';
$loader = new App\Core\Autoloader(__DIR__ . '/../app');
$loader->register();

$db = (new App\Core\Database(require __DIR__ . '/../config/database.php'))->pdo();
$db->exec('DELETE FROM locks WHERE expires_at < NOW()');
$db->exec('DELETE FROM waiting_states WHERE status="active" AND expires_at IS NOT NULL AND expires_at < NOW()');
$db->exec('UPDATE waiting_states SET status="expired", updated_at=NOW() WHERE status="active" AND expires_at IS NOT NULL AND expires_at < NOW()');

echo "cleanup done\n";
