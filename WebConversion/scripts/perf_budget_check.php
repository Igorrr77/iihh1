<?php

declare(strict_types=1);

$roomShell = __DIR__ . '/../public/room-shell.html';
if (!is_file($roomShell)) {
    echo "room-shell.html not found\n";
    exit(1);
}

$content = (string) file_get_contents($roomShell);
$totalBytes = filesize($roomShell);
if ($totalBytes === false) {
    $totalBytes = strlen($content);
}

preg_match('/<style>(.*?)<\/style>/s', $content, $styleMatch);
preg_match('/<script>(.*?)<\/script>/s', $content, $scriptMatch);

$cssBytes = isset($styleMatch[1]) ? strlen($styleMatch[1]) : 0;
$jsBytes = isset($scriptMatch[1]) ? strlen($scriptMatch[1]) : 0;

$maxTotal = 24 * 1024;
$maxCss = 10 * 1024;
$maxJs = 10 * 1024;

$errors = [];
if ($totalBytes > $maxTotal) {
    $errors[] = "Total HTML budget exceeded: {$totalBytes} > {$maxTotal}";
}
if ($cssBytes > $maxCss) {
    $errors[] = "CSS budget exceeded: {$cssBytes} > {$maxCss}";
}
if ($jsBytes > $maxJs) {
    $errors[] = "JS budget exceeded: {$jsBytes} > {$maxJs}";
}

if ($errors !== []) {
    foreach ($errors as $error) {
        echo $error . "\n";
    }
    exit(1);
}

echo "Perf budget passed (html={$totalBytes}, css={$cssBytes}, js={$jsBytes})\n";
