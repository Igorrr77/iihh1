<?php

declare(strict_types=1);

require __DIR__ . '/../app/Graph/GraphValidator.php';
require __DIR__ . '/../app/Graph/GraphCompiler.php';

use App\Graph\GraphCompiler;
use App\Graph\GraphValidator;

$graph = [
    'nodes' => [
        ['uuid' => 'start', 'type' => 'start', 'config' => ['trigger_type' => 'command', 'command' => '/start']],
        ['uuid' => 'cond', 'type' => 'condition', 'config' => ['left_key' => 'input', 'operator' => 'eq', 'right_value' => 'yes']],
        ['uuid' => 'yes', 'type' => 'send_text', 'config' => ['text' => 'Yes branch']],
        ['uuid' => 'no', 'type' => 'send_text', 'config' => ['text' => 'No branch']],
    ],
    'edges' => [
        ['uuid' => 'e1', 'from' => ['node_uuid' => 'start', 'port' => 'next'], 'to' => ['node_uuid' => 'cond']],
        ['uuid' => 'e2', 'from' => ['node_uuid' => 'cond', 'port' => 'true'], 'to' => ['node_uuid' => 'yes']],
        ['uuid' => 'e3', 'from' => ['node_uuid' => 'cond', 'port' => 'false'], 'to' => ['node_uuid' => 'no']],
    ],
];

$validator = new GraphValidator();
$validation = $validator->validate($graph);
if (($validation['status'] ?? '') !== 'valid') {
    fwrite(STDERR, "Expected graph to be valid before compilation\n");
    exit(1);
}

$compiled = (new GraphCompiler())->compile($graph);
if (empty($compiled['hash']) || !is_string($compiled['hash'])) {
    fwrite(STDERR, "Compiled hash is missing\n");
    exit(1);
}

$entry = $compiled['entrypoints'][0]['node_uuid'] ?? null;
if ($entry !== 'start') {
    fwrite(STDERR, "Unexpected entrypoint node\n");
    exit(1);
}

$condEdges = $compiled['nodes']['cond']['next'] ?? [];
$ports = array_map(static fn (array $e): string => (string)($e['port'] ?? ''), $condEdges);
if (!in_array('true', $ports, true) || !in_array('false', $ports, true)) {
    fwrite(STDERR, "Condition ports are not preserved in compilation\n");
    exit(1);
}

echo "Graph compile pipeline smoke check passed\n";
