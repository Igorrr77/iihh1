<?php

declare(strict_types=1);

use App\Services\SegmentService;

require_once __DIR__ . '/../bootstrap.php';

$service = new SegmentService();
assertTrue($service->detectSegment(false, false, false) === 'no_show', 'No join -> no_show');
assertTrue($service->detectSegment(true, false, false, true, 120) === 'drop_early', 'Short watch -> drop_early');
assertTrue($service->detectSegment(true, false, false, true, 900) === 'left_before_offer', 'Joined without offer -> left_before_offer');
assertTrue($service->detectSegment(true, true, false) === 'attended_offer_no_purchase', 'Reached offer without purchase -> attended_offer_no_purchase');
assertTrue($service->detectSegment(true, true, true) === 'purchased', 'Purchase -> purchased');
