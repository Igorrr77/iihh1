<?php

declare(strict_types=1);

use App\Services\OfferService;

require_once __DIR__ . '/../bootstrap.php';

$service = new OfferService();
$exp = $service->expiresAt('2030-01-01 10:00:00', 600);
assertTrue($exp === '2030-01-01 10:10:00', 'Offer expiration calculation failed');
