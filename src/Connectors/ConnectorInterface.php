<?php

declare(strict_types=1);

namespace App\Connectors;

interface ConnectorInterface
{
    public function provider(): string;

    /**
     * @return array<int, array<string,mixed>>
     */
    public function fetch(array $task): array;
}
