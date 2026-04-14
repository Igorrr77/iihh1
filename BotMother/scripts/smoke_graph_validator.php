<?php

declare(strict_types=1);

require __DIR__ . '/../app/Graph/GraphValidator.php';

use App\Graph\GraphValidator;

$validator = new GraphValidator();

$validGraph = [
    'nodes' => [
        ['uuid' => 'start-1', 'type' => 'start', 'config' => []],
        ['uuid' => 'cond-1', 'type' => 'condition', 'config' => ['left_key' => 'input', 'operator' => 'eq', 'right_value' => 'yes']],
        ['uuid' => 'http-1', 'type' => 'http_request', 'config' => ['url' => 'https://example.com']],
        ['uuid' => 'send-1', 'type' => 'send_text', 'config' => ['text' => 'ok']],
    ],
    'edges' => [
        ['uuid' => 'e1', 'from' => ['node_uuid' => 'start-1', 'port' => 'next'], 'to' => ['node_uuid' => 'cond-1']],
        ['uuid' => 'e2', 'from' => ['node_uuid' => 'cond-1', 'port' => 'true'], 'to' => ['node_uuid' => 'http-1']],
        ['uuid' => 'e3', 'from' => ['node_uuid' => 'cond-1', 'port' => 'false'], 'to' => ['node_uuid' => 'send-1']],
    ],
];

$invalidGraph = [
    'nodes' => [
        ['uuid' => 'start-1', 'type' => 'start', 'config' => []],
        ['uuid' => 'cond-1', 'type' => 'condition', 'config' => []],
        ['uuid' => 'http-1', 'type' => 'http_request', 'config' => []],
    ],
    'edges' => [
        ['uuid' => 'e1', 'from' => ['node_uuid' => 'start-1', 'port' => 'next'], 'to' => ['node_uuid' => 'cond-1']],
        ['uuid' => 'e2', 'from' => ['node_uuid' => 'cond-1', 'port' => 'true'], 'to' => ['node_uuid' => 'http-1']],
    ],
];

$valid = $validator->validate($validGraph);
$invalid = $validator->validate($invalidGraph);

if (($valid['status'] ?? '') !== 'valid') {
    fwrite(STDERR, "Expected valid graph to pass validation\n");
    exit(1);
}

$invalidCodes = array_map(static fn (array $e): string => (string)($e['code'] ?? ''), $invalid['errors'] ?? []);
$warningsCodes = array_map(static fn (array $w): string => (string)($w['code'] ?? ''), $invalid['warnings'] ?? []);

foreach (['HTTP_REQUEST_URL_REQUIRED', 'CONDITION_BRANCH_MISSING'] as $expected) {
    if (!in_array($expected, $invalidCodes, true)) {
        fwrite(STDERR, "Expected error code missing: {$expected}\n");
        exit(1);
    }
}

if (!in_array('CONDITION_LEFT_KEY_DEFAULTED', $warningsCodes, true)) {
    fwrite(STDERR, "Expected warning code missing: CONDITION_LEFT_KEY_DEFAULTED\n");
    exit(1);
}

echo "GraphValidator smoke check passed\n";
