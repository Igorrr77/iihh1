<?php
return [
    'webhook_base' => getenv('TELEGRAM_WEBHOOK_BASE') ?: 'https://example.com/webhook.php',
    'timeout' => 20,
];
