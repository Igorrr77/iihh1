<?php

declare(strict_types=1);

$base = $argv[1] ?? 'http://127.0.0.1:8080';
$checks = [
    ['/health', 200],
    ['/api/chat/stream?webinar_id=missing', [404, 500]],
];

foreach ($checks as [$path, $expected]) {
    $ch = curl_init($base . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $ok = is_array($expected) ? in_array($code, $expected, true) : ($code === $expected);
    if (!$ok) {
        $exp = is_array($expected) ? implode(',', $expected) : (string) $expected;
        echo "E2E fail {$path} expected {$exp}, got {$code}\n";
        exit(1);
    }
}

echo "E2E tests passed\n";
