<?php

declare(strict_types=1);

$base = $argv[1] ?? 'http://127.0.0.1:8080';

$checks = [
    ['name' => 'health', 'method' => 'GET', 'url' => $base . '/health', 'expected' => 200],
    ['name' => 'auth_login_validation', 'method' => 'POST', 'url' => $base . '/api/auth/login', 'json' => [], 'expected' => 422],
    ['name' => 'track_event_validation', 'method' => 'POST', 'url' => $base . '/api/analytics/track-event', 'json' => [], 'expected' => [422,500]],
];

$failed = [];
foreach ($checks as $c) {
    $ch = curl_init($c['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $c['method']);
    if (isset($c['json'])) {
        $payload = json_encode($c['json'], JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $expected = $c['expected'];
    $ok = is_array($expected) ? in_array($code, $expected, true) : ($code === $expected);
    if (!$ok) {
        $failed[] = ['name' => $c['name'], 'expected' => $expected, 'actual' => $code];
        $expText = is_array($expected) ? implode(',', $expected) : (string) $expected;
        echo "FAIL {$c['name']} expected={$expText} actual={$code}\n";
    } else {
        echo "PASS {$c['name']} ({$code})\n";
    }
}

if ($failed !== []) {
    exit(1);
}

echo "Integration smoke passed\n";
