<?php

declare(strict_types=1);

$projectRoot = realpath(__DIR__ . '/..');
if ($projectRoot === false) {
    throw new RuntimeException('Project root not found');
}

$releasesDir = $projectRoot . '/releases';
$currentLink = $projectRoot . '/current';
$releasePath = $releasesDir . '/' . date('Ymd_His');

$env = [];
$envPath = $projectRoot . '/.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v);
    }
}

$appUrl = (string) ($env['APP_URL'] ?? getenv('APP_URL') ?: 'http://1968.us/WebConversion');
$healthUrl = rtrim($appUrl, '/') . '/health';

if (!is_dir($releasesDir) && !mkdir($releasesDir, 0775, true) && !is_dir($releasesDir)) {
    throw new RuntimeException('Не удалось создать папку releases');
}
if (!is_dir($releasePath) && !mkdir($releasePath, 0775, true) && !is_dir($releasePath)) {
    throw new RuntimeException('Не удалось создать папку релиза: ' . $releasePath);
}

$exclude = ['releases', 'storage/logs', 'current'];
$items = scandir($projectRoot) ?: [];
foreach ($items as $item) {
    if ($item === '.' || $item === '..' || in_array($item, $exclude, true)) {
        continue;
    }

    $source = $projectRoot . DIRECTORY_SEPARATOR . $item;
    $target = $releasePath . DIRECTORY_SEPARATOR . $item;

    if (is_dir($source)) {
        passthru('cp -R ' . escapeshellarg($source) . ' ' . escapeshellarg($target));
    } else {
        copy($source, $target);
    }
}

$prevTarget = is_link($currentLink) ? readlink($currentLink) : null;
if (is_link($currentLink) || file_exists($currentLink)) {
    unlink($currentLink);
}
symlink($releasePath, $currentLink);

$healthCode = 1;
if (function_exists('curl_init')) {
    $ch = curl_init($healthUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $healthCode = ($http === 200) ? 0 : 1;
} else {
    $response = @file_get_contents($healthUrl);
    $healthCode = ($response !== false) ? 0 : 1;
}

if ($healthCode !== 0) {
    unlink($currentLink);
    if (is_string($prevTarget) && $prevTarget !== '') {
        symlink($prevTarget, $currentLink);
    }
    echo "Health-check failed ({$healthUrl}). Rollback complete.\n";
    exit(1);
}

echo "Deploy complete. Active release: {$releasePath}\n";
echo "Health-check passed: {$healthUrl}\n";
if (is_string($prevTarget) && $prevTarget !== '') {
    echo "Previous release: {$prevTarget}\n";
}
