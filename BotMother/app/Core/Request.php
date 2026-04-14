<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $query,
        private readonly array $body,
        private readonly array $headers,
        private readonly string $rawBody,
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $raw = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);
        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            $_GET,
            is_array($json) ? $json : $_POST,
            $headers,
            $raw,
        );
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->uri; }
    public function input(string $key, mixed $default = null): mixed { return $this->body[$key] ?? $default; }
    public function body(): array { return $this->body; }
    public function rawBody(): string { return $this->rawBody; }
    public function header(string $key): ?string { return $this->headers[$key] ?? $this->headers[strtolower($key)] ?? null; }
}
