<?php

declare(strict_types=1);

namespace App\Services;

class Logger
{
    public function log(string $channel, string $message): void
    {
        $line = sprintf("[%s] %s\n", gmdate('c'), $message);
        file_put_contents(root_path("storage/logs/{$channel}.log"), $line, FILE_APPEND);
    }
}
