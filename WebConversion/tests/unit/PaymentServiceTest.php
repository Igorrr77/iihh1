<?php

declare(strict_types=1);

use App\Services\PaymentService;

require_once __DIR__ . '/../bootstrap.php';

$service = new PaymentService();
$url = $service->buildCheckoutUrl('stripe', 'pay_123');
assertTrue(str_contains($url, 'pay_123'), 'Checkout URL should contain payment ID');

$payload = '{"payment_id":"pay_123","status":"paid","event_id":"evt_1"}';
$secret = 'top_secret';
$signature = hash_hmac('sha256', $payload, $secret);
assertTrue($service->verifyWebhookSignature($payload, $signature, $secret), 'Valid signature should pass');
assertTrue(!$service->verifyWebhookSignature($payload, 'bad', $secret), 'Invalid signature should fail');

$secretMap = 'v1:old_secret,v2:top_secret';
assertTrue($service->verifyWebhookSignature($payload, $signature, $secretMap, 'v2'), 'Key rotation map should validate by key id');

$parsed = $service->parseWebhookPayload($payload);
assertTrue(is_array($parsed), 'Valid payload should parse');
assertTrue(($parsed['event_id'] ?? '') === 'evt_1', 'Parsed payload should include event_id');
assertTrue($service->parseWebhookPayload('{"payment_id":"pay_123"}') === null, 'Invalid payload should return null');

assertTrue($service->normalizeProvider('PAYPAL') === 'paypal', 'Provider normalization should support PayPal');
assertTrue($service->normalizeProvider('unknown') === 'stripe', 'Unknown provider should fallback to stripe');
assertTrue($service->normalizeCurrency('eur') === 'EUR', 'Currency normalization should uppercase');
assertTrue($service->normalizeCurrency('bad123') === 'USD', 'Invalid currency should fallback to USD');
assertTrue($service->validateAmountCents(50), 'Min amount should be accepted');
assertTrue(!$service->validateAmountCents(10), 'Too small amount should be rejected');
assertTrue($service->maxRetryAttempts() === 3, 'Retry attempts policy should be 3');


$matrix = $service->pspMatrix();
assertTrue(count($matrix) >= 4, 'PSP matrix should include all required providers');
