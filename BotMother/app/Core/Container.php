<?php

declare(strict_types=1);

namespace App\Core;

final class Container
{
    private array $entries = [];
    private array $resolved = [];

    public function set(string $id, callable $resolver): void
    {
        $this->entries[$id] = $resolver;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }
        if (!isset($this->entries[$id])) {
            throw new \RuntimeException("Service {$id} is not registered");
        }
        return $this->resolved[$id] = ($this->entries[$id])($this);
    }
}
