<?php

declare(strict_types=1);

use App\Services\EmailAutomationService;

require_once __DIR__ . '/../bootstrap.php';

$service = new EmailAutomationService();
assertTrue($service->templateBySegment('no_show') === 'nudge_attend_replay', 'Template for no-show segment mismatch');
assertTrue($service->templateBySegment('purchased') === 'welcome_onboard', 'Template for purchased mismatch');
assertTrue($service->templateBySegment('unknown') === 'generic_followup', 'Fallback template mismatch');

$orch = $service->orchestrationBySegment('drop_early');
assertTrue(isset($orch['voice']), 'Drop early should include voice channel');


$policy = $service->channelRetryPolicy('sms');
assertTrue(($policy['max_attempts'] ?? 0) === 4, 'SMS retry max attempts should be 4');
assertTrue(($policy['base_delay_sec'] ?? 0) === 180, 'SMS retry delay should be 180 sec');

assertTrue($service->normalizeCrmProvider('Salesforce') === 'salesforce', 'CRM provider normalization should support Salesforce');
assertTrue($service->normalizeCrmProvider('unknown') === 'hubspot', 'Unknown CRM provider should fallback to hubspot');
