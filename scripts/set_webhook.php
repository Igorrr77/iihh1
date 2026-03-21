<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if ($argc < 2) {
    echo "Usage: php scripts/set_webhook.php <tenant_slug>\n";
    exit(1);
}

$slug = $argv[1];
$tenant = $db->query('SELECT telegram_bot_token FROM tenants WHERE slug = :slug LIMIT 1', ['slug' => $slug])->fetch();
if (!$tenant) {
    echo "Tenant not found\n";
    exit(1);
}

$url = rtrim($config['app']['base_url'], '/') . '/webhook/telegram/' . rawurlencode($slug) . '/' . rawurlencode($config['security']['webhook_secret']);
$apiUrl = 'https://api.telegram.org/bot' . $tenant['telegram_bot_token'] . '/setWebhook';

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['url' => $url]),
    CURLOPT_RETURNTRANSFER => true,
]);

$result = curl_exec($ch);
curl_close($ch);

echo $result . PHP_EOL;
