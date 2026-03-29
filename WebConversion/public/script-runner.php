<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$key = (string) ($_GET['key'] ?? $_POST['key'] ?? '');
$expectedKey = (string) (getenv('SCRIPT_RUNNER_KEY') ?: getenv('ADMIN_API_KEY'));
if ($expectedKey === '' || !hash_equals($expectedKey, $key)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

$task = (string) ($_GET['task'] ?? $_POST['task'] ?? '');
$root = realpath(__DIR__ . '/..') ?: dirname(__DIR__);

$map = [
    'migrate' => ['script' => 'scripts/migrate.php'],
    'tests' => ['script' => 'tests/run.php'],
    'quality_gate' => ['script' => 'scripts/quality_gate.php'],
    'security_scan' => ['script' => 'scripts/security_scan.php'],
    'backup_db' => ['script' => 'scripts/backup_db.php'],
    'deploy' => ['script' => 'scripts/deploy.php'],
    'disaster_drill' => ['script' => 'scripts/disaster_drill.php'],
    'ga_gate_check' => ['script' => 'scripts/ga_gate_check.php'],
    'go_no_go_report' => ['script' => 'scripts/go_no_go_report.php', 'args' => ['--advisory']],
    'ga_stabilization_report' => ['script' => 'scripts/ga_stabilization_report.php', 'args' => ['--advisory']],
    'provider_matrix_check' => ['script' => 'scripts/provider_matrix_check.php', 'args' => ['--advisory']],
    'production_stability_check' => ['script' => 'scripts/production_stability_check.php', 'args' => ['--advisory']],
    'create_admin' => ['script' => 'scripts/create_admin.php', 'params' => ['email', 'password', 'role']],
];

if (!isset($map[$task])) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'unknown_task',
        'allowed' => array_keys($map),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$entry = $map[$task];
$script = (string) ($entry['script'] ?? '');
$args = (array) ($entry['args'] ?? []);

foreach ((array) ($entry['params'] ?? []) as $param) {
    $value = (string) ($_GET[$param] ?? $_POST[$param] ?? '');
    if ($value === '' && $param !== 'role') {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => "missing_param_{$param}"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($value !== '') {
        $args[] = $value;
    }
}

$cmd = 'cd ' . escapeshellarg($root) . ' && php ' . escapeshellarg($script);
foreach ($args as $arg) {
    $cmd .= ' ' . escapeshellarg((string) $arg);
}

$output = [];
$code = 0;
exec($cmd . ' 2>&1', $output, $code);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'task' => $task,
    'exit_code' => $code,
    'command' => $cmd,
    'output' => $output,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
