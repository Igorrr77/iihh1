<?php

declare(strict_types=1);

require __DIR__ . '/../database/migrate.php';
file_put_contents(__DIR__ . '/../storage/logs/update.log', '[' . date('c') . "] update executed\n", FILE_APPEND);
header('Content-Type: application/json');
echo json_encode(['status' => 'updated']);
