<?php

declare(strict_types=1);

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    throw new RuntimeException('Root not found');
}

$patterns = [
    '/password\s*=\s*["\"][^"\"]{4,}["\"]/i',
    '/api[_-]?key\s*=\s*["\"][^"\"]{8,}["\"]/i',
    '/secret\s*=\s*["\"][^"\"]{8,}["\"]/i',
];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$violations = [];

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    if (str_contains($path, '/vendor/') || str_contains($path, '/.git/')) {
        continue;
    }
    if (!preg_match('/\.(php|md|env|txt|yml)$/', $path)) {
        continue;
    }

    $content = file_get_contents($path);
    if (!is_string($content)) {
        continue;
    }

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content) === 1) {
            $violations[] = $path;
            echo "Potential secret pattern: {$path}\n";
            break;
        }
    }
}

if ($violations !== []) {
    exit(1);
}

echo "Security scan passed (no obvious hardcoded secrets)\n";
