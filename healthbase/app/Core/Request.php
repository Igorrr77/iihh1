<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    public string $method;
    public string $uri;
    public array $get;
    public array $post;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $base = app_base_path();
        $cleanPath = (string)($path ?: '/');
        if ($base !== '' && str_starts_with($cleanPath, $base)) {
            $cleanPath = substr($cleanPath, strlen($base)) ?: '/';
        }
        $this->uri = rtrim($cleanPath, '/') ?: '/';
        $this->get = $_GET;
        $this->post = $_POST;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }
}
