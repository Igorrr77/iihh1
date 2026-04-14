<?php

declare(strict_types=1);

use App\Services\EmbedSdkContractService;

require_once __DIR__ . '/../bootstrap.php';

$service = new EmbedSdkContractService();
$contract = $service->contract();

assertTrue((string) ($contract['sdk_contract_version'] ?? '') === 'v1', 'SDK contract version must be v1');
assertTrue(isset($contract['iframe_to_host_events']['ready']), 'ready event must exist');
assertTrue(isset($contract['iframe_to_host_events']['checkout_open']), 'checkout_open event must exist');
assertTrue(isset($contract['host_to_iframe_events']['open_checkout']), 'open_checkout command must exist');
assertTrue($service->isSupportedEvent('play') === true, 'play event should be supported');
assertTrue($service->isSupportedEvent('unknown_event') === false, 'unknown event should be unsupported');
