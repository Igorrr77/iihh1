<?php

declare(strict_types=1);

use App\Services\AccessPolicyService;

require_once __DIR__ . '/../bootstrap.php';

$service = new AccessPolicyService();

$result = $service->validate('name_email_phone', [
    'name' => 'Ivan',
    'email' => 'ivan@example.com',
    'phone' => '+123456',
]);
assertTrue(($result['ok'] ?? false) === true, 'Expected valid payload to pass');

$result = $service->validate('name_email', [
    'name' => 'Ivan',
    'email' => 'bad_email',
]);
assertTrue(($result['ok'] ?? true) === false, 'Expected invalid email to fail');

$result = $service->validate('name_email_phone', [
    'name' => 'Ivan',
    'email' => 'ivan@example.com',
]);
assertTrue(($result['ok'] ?? true) === false, 'Expected missing phone to fail');
