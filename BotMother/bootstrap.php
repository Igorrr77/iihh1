<?php

declare(strict_types=1);

use App\Core\App;

require_once __DIR__ . '/app/Core/Autoloader.php';

$autoloader = new App\Core\Autoloader(__DIR__ . '/app');
$autoloader->register();

$config = require __DIR__ . '/config/app.php';
$dbConfig = require __DIR__ . '/config/database.php';
$security = require __DIR__ . '/config/security.php';
$queue = require __DIR__ . '/config/queue.php';
$telegram = require __DIR__ . '/config/telegram.php';

return new App($config, $dbConfig, $security, $queue, $telegram);
