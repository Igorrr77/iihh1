<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
