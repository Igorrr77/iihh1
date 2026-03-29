<?php

declare(strict_types=1);

namespace App\Services;

final class EmbedSdkContractService
{
    /**
     * @return array<string, mixed>
     */
    public function contract(): array
    {
        return [
            'sdk_contract_version' => 'v1',
            'host_to_iframe_events' => [
                'play' => ['required_payload' => []],
                'pause' => ['required_payload' => []],
                'seek' => ['required_payload' => ['seconds']],
                'set_room_state' => ['required_payload' => ['state']],
                'open_checkout' => ['required_payload' => ['offer_id']],
            ],
            'iframe_to_host_events' => [
                'ready' => ['required_payload' => []],
                'play' => ['required_payload' => ['current_time_sec']],
                'pause' => ['required_payload' => ['current_time_sec']],
                'ended' => ['required_payload' => ['duration_sec']],
                'cta_click' => ['required_payload' => ['cta_id']],
                'checkout_open' => ['required_payload' => ['offer_id']],
                'error' => ['required_payload' => ['code', 'message']],
            ],
            'error_codes' => [
                'UNAUTHORIZED_ORIGIN',
                'INVALID_PAYLOAD',
                'UNSUPPORTED_VERSION',
                'RUNTIME_FAILURE',
            ],
        ];
    }

    public function isSupportedEvent(string $name): bool
    {
        $contract = $this->contract();
        return isset($contract['host_to_iframe_events'][$name]) || isset($contract['iframe_to_host_events'][$name]);
    }
}
