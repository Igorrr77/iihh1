<?php

declare(strict_types=1);

$commands = [
    'php tests/run.php',
    "find app public config scripts tests -name '*.php' -print0 | xargs -0 -n1 php -l",
    'php scripts/perf_budget_check.php',
];

foreach ($commands as $cmd) {
    passthru($cmd, $code);
    if ($code !== 0) {
        echo "Quality gate failed: {$cmd}\n";
        exit(1);
    }
}

echo "Quality gate passed\n";
