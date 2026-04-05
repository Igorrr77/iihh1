<?php

declare(strict_types=1);

namespace App\Services;

class YouTubeService
{
    private string $base = 'https://www.googleapis.com/youtube/v3';

    public function __construct(private string $apiKey, private Logger $logger)
    {
    }

    public function fetchUploadsPlaylistId(string $channelId): ?string
    {
        $data = $this->request('/channels', [
            'part' => 'contentDetails',
            'id' => $channelId,
        ]);

        return $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? null;
    }

    public function fetchPlaylistItems(string $playlistId, ?string $pageToken = null): array
    {
        return $this->request('/playlistItems', [
            'part' => 'snippet,contentDetails,status',
            'playlistId' => $playlistId,
            'maxResults' => 50,
            'pageToken' => $pageToken,
        ]);
    }

    public function fetchVideoDetails(array $videoIds): array
    {
        return $this->request('/videos', [
            'part' => 'snippet,contentDetails,status',
            'id' => implode(',', $videoIds),
            'maxResults' => 50,
        ]);
    }

    private function request(string $path, array $params): array
    {
        $params['key'] = $this->apiKey;
        $url = $this->base . $path . '?' . http_build_query($params);
        $attempts = 0;

        do {
            $attempts++;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($body !== false && $code >= 200 && $code < 300) {
                return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            }

            $this->logger->log('sync', "YouTube API error {$code}: {$error}");
            usleep(200000 * $attempts);
        } while ($attempts < 3);

        return ['items' => []];
    }
}
