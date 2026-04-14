<?php

declare(strict_types=1);

namespace App\Services;

final class EmailAutomationService
{
    public function templateBySegment(string $segment): string
    {
        return match ($segment) {
            'no_show' => 'nudge_attend_replay',
            'drop_early', 'left_before_offer' => 'return_to_offer',
            'attended_offer_no_purchase' => 'final_offer_push',
            'purchased' => 'welcome_onboard',
            default => 'generic_followup',
        };
    }

    /**
     * @return array<string, string>
     */
    public function orchestrationBySegment(string $segment): array
    {
        return match ($segment) {
            'no_show' => ['email' => 'nudge_attend_replay', 'sms' => 'sms_replay_link'],
            'drop_early' => ['email' => 'return_to_offer', 'voice' => 'voice_call_reminder'],
            'attended_offer_no_purchase' => ['email' => 'final_offer_push', 'sms' => 'sms_final_offer'],
            'purchased' => ['email' => 'welcome_onboard'],
            default => ['email' => 'generic_followup'],
        };
    }

    /**
     * @return array<string, int>
     */
    public function channelRetryPolicy(string $channel): array
    {
        return match ($channel) {
            'email' => ['max_attempts' => 5, 'base_delay_sec' => 300],
            'sms' => ['max_attempts' => 4, 'base_delay_sec' => 180],
            'voice' => ['max_attempts' => 3, 'base_delay_sec' => 600],
            default => ['max_attempts' => 3, 'base_delay_sec' => 300],
        };
    }

    public function normalizeCrmProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));
        $allowed = ['hubspot', 'salesforce', 'pipedrive', 'zoho', 'dynamics', 'amocrm', 'bitrix24'];
        return in_array($provider, $allowed, true) ? $provider : 'hubspot';
    }
}
