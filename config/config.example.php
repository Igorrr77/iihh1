<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Sales Speech Intelligence',
        'base_url' => 'https://your-domain.example',
        'session_name' => 'ssi_admin',
    ],
    'db' => [
        'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=sales_ai;charset=utf8mb4',
        'user' => 'root',
        'password' => 'secret',
    ],
    'gemini' => [
        'api_key' => 'YOUR_GEMINI_API_KEY',
        'model' => 'gemini-3.1-flash-lite',
    ],
    'security' => [
        'webhook_secret' => 'set-a-strong-secret',
    ],
];
