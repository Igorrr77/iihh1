<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public function __construct(private readonly string $file)
    {
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $line = sprintf("[%s] %s %s %s\n", date('c'), $level, $message, json_encode($context, JSON_UNESCAPED_UNICODE));
        file_put_contents($this->file, $line, FILE_APPEND);
    }
}
