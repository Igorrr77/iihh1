<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$input = file_get_contents('php://input') ?: '{}';
$payload = json_decode($input, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

// Заготовка под callback API событий провайдеров.
echo json_encode(['ok' => true, 'received' => $payload]);
