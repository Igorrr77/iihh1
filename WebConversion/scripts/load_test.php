<?php

declare(strict_types=1);

$base = $argv[1] ?? 'http://127.0.0.1:8080';
$iterations = isset($argv[2]) ? max(1, (int) $argv[2]) : 50;

$start = microtime(true);
$ok = 0;
for ($i = 0; $i < $iterations; $i++) {
    $ch = curl_init($base . '/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200) {
        $ok++;
    }
}
$elapsed = microtime(true) - $start;
$avgMs = ($elapsed / $iterations) * 1000;

echo "Iterations: {$iterations}\n";
echo "HTTP 200: {$ok}\n";
echo 'Elapsed sec: ' . round($elapsed, 3) . "\n";
echo 'Avg ms/request: ' . round($avgMs, 2) . "\n";

if ($ok !== $iterations) {
    exit(1);
}
