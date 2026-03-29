<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $logFile = dirname(__DIR__, 2) . '/storage/logs/app.log';
        $line = sprintf(
            "%s [%s] %s %s\n",
            gmdate(DATE_ATOM),
            $level,
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        file_put_contents($logFile, $line, FILE_APPEND);
    }
}
