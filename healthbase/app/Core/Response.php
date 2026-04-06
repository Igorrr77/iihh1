<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function view(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        extract($data);
        require root_path('app/Views/' . $template . '.php');
    }

    public static function redirect(string $url): void
    {
        $target = $url;
        if (!preg_match('#^https?://#i', $url)) {
            $target = url($url);
        }
        header('Location: ' . $target);
        exit;
    }

    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
