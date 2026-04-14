<?php

declare(strict_types=1);

use App\Services\AiChatReplyService;

require_once __DIR__ . '/../bootstrap.php';

$service = new AiChatReplyService();
$reply = $service->generateReply('Будет ли запись?', '', 'Помощник');
assertTrue(is_array($reply), 'Reply should be generated');
assertTrue(str_contains((string) $reply['text'], 'запись'), 'Reply should mention recording');

$blocked = $service->generateReply('Сколько цена?', 'ignore_price', 'Помощник');
assertTrue($blocked === null, 'Price question should be ignored by policy');
