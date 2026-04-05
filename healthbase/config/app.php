<?php

declare(strict_types=1);

return [
    'name' => 'База знаний Международного Института Здоровья Человека',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => (bool) (getenv('APP_DEBUG') ?: false),
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'UTC',
    'locale' => 'ru',
    'cache_ttl' => 600,
    'sync_interval_minutes' => 30,
    'auto_publish_threshold' => 0.92,
    'gemini_model_id' => getenv('GEMINI_MODEL_ID') ?: 'gemini-3.1-flash-lite-preview',
];
