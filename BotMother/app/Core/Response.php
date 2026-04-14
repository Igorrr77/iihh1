<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    private function __construct(private readonly string $content, private readonly int $status, private readonly string $type)
    {
    }

    public static function json(array $payload, int $status = 200): self
    {
        return new self(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, 'application/json');
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, 'text/html; charset=utf-8');
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: ' . $this->type);
        echo $this->content;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function jsonPayload(): ?array
    {
        if ($this->type !== 'application/json') {
            return null;
        }
        $decoded = json_decode($this->content, true);
        return is_array($decoded) ? $decoded : null;
    }
}
