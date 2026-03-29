<?php

declare(strict_types=1);

namespace App\Services;

final class VideoProviderAdapter
{
    public function resolvePlayback(string $provider, string $externalId): array
    {
        $url = match ($provider) {
            'youtube' => 'https://www.youtube.com/embed/' . $externalId,
            'vimeo' => 'https://player.vimeo.com/video/' . $externalId,
            'kinescope' => 'https://kinescope.io/embed/' . $externalId,
            'bunny' => 'https://iframe.mediadelivery.net/embed/' . $externalId,
            default => 'https://cdn.example/player/' . $externalId,
        };

        return [
            'url' => $url,
            'quality' => '1080p',
            'provider' => $provider,
            'external_id' => $externalId,
            'provider_api' => $this->providerApiMeta($provider, $externalId),
        ];
    }

    private function providerApiMeta(string $provider, string $externalId): array
    {
        return match ($provider) {
            'youtube' => ['oembed' => 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $externalId . '&format=json'],
            'vimeo' => ['oembed' => 'https://vimeo.com/api/oembed.json?url=https://vimeo.com/' . $externalId],
            'kinescope' => ['docs' => 'https://developers.kinescope.io/'],
            'bunny' => ['docs' => 'https://docs.bunny.net/docs/stream-api'],
            default => [],
        };
    }
}
