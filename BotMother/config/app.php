<?php
return [
    'name' => 'BotMother',
    'env' => getenv('APP_ENV') ?: 'production',
    'version' => '0.1.0',
    'installed_flag' => __DIR__ . '/../storage/installed.lock',
];
