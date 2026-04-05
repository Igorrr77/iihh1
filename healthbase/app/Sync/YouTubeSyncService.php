<?php

declare(strict_types=1);

namespace App\Sync;

use App\Services\YouTubeService;
use App\Services\Logger;
use PDO;

class YouTubeSyncService
{
    public function __construct(private PDO $pdo, private YouTubeService $youtube, private Logger $logger)
    {
    }

    public function run(string $channelId): array
    {
        $run = ['fetched' => 0, 'inserted' => 0, 'updated' => 0, 'short' => 0, 'queued_ai' => 0];
        $uploads = $this->youtube->fetchUploadsPlaylistId($channelId);
        if (!$uploads) {
            throw new \RuntimeException('Uploads playlist not found');
        }

        $page = null;
        do {
            $chunk = $this->youtube->fetchPlaylistItems($uploads, $page);
            $ids = [];
            foreach ($chunk['items'] ?? [] as $item) {
                $id = $item['contentDetails']['videoId'] ?? null;
                if ($id) {
                    $ids[] = $id;
                }
            }

            if ($ids) {
                $details = $this->youtube->fetchVideoDetails($ids);
                foreach ($details['items'] ?? [] as $video) {
                    $run['fetched']++;
                    $this->upsertVideo($video, $channelId, $run);
                }
            }

            $page = $chunk['nextPageToken'] ?? null;
        } while ($page);

        $this->logger->log('sync', 'Sync run: ' . json_encode($run, JSON_UNESCAPED_UNICODE));

        return $run;
    }

    private function upsertVideo(array $payload, string $channelId, array &$run): void
    {
        $videoId = $payload['id'] ?? '';
        $status = $payload['status']['privacyStatus'] ?? 'private';
        if (in_array($status, ['private', 'unlisted'], true)) {
            return;
        }

        $durationIso = $payload['contentDetails']['duration'] ?? 'PT0S';
        $seconds = $this->iso8601ToSeconds($durationIso);
        $isLong = $seconds > 300 ? 1 : 0;

        $exists = $this->pdo->prepare('SELECT id, title, description, duration_iso8601 FROM videos WHERE youtube_video_id = :id LIMIT 1');
        $exists->execute(['id' => $videoId]);
        $row = $exists->fetch();

        $data = [
            'youtube_video_id' => $videoId,
            'youtube_channel_id' => $channelId,
            'title' => $payload['snippet']['title'] ?? '',
            'description' => $payload['snippet']['description'] ?? '',
            'published_at' => gmdate('Y-m-d H:i:s', strtotime($payload['snippet']['publishedAt'] ?? 'now')),
            'duration_seconds' => $seconds,
            'duration_iso8601' => $durationIso,
            'thumbnail_default' => $payload['snippet']['thumbnails']['default']['url'] ?? '',
            'thumbnail_medium' => $payload['snippet']['thumbnails']['medium']['url'] ?? '',
            'thumbnail_high' => $payload['snippet']['thumbnails']['high']['url'] ?? '',
            'thumbnail_maxres' => $payload['snippet']['thumbnails']['maxres']['url'] ?? null,
            'url' => 'https://www.youtube.com/watch?v=' . $videoId,
            'embed_url' => 'https://www.youtube.com/embed/' . $videoId,
            'status' => 'synced',
            'is_public' => 1,
            'is_long_video' => $isLong,
            'source_payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'last_synced_at' => gmdate('Y-m-d H:i:s'),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ];

        if (!$row) {
            $sql = 'INSERT INTO videos (youtube_video_id, youtube_channel_id, title, description, published_at, duration_seconds, duration_iso8601, thumbnail_default, thumbnail_medium, thumbnail_high, thumbnail_maxres, url, embed_url, status, is_public, is_long_video, source_payload_json, last_synced_at, created_at, updated_at)
            VALUES (:youtube_video_id,:youtube_channel_id,:title,:description,:published_at,:duration_seconds,:duration_iso8601,:thumbnail_default,:thumbnail_medium,:thumbnail_high,:thumbnail_maxres,:url,:embed_url,:status,:is_public,:is_long_video,:source_payload_json,:last_synced_at,:updated_at,:updated_at)';
            $this->pdo->prepare($sql)->execute($data);
            $run['inserted']++;
            if ($isLong) {
                $this->queueAiJob($videoId, 'classify');
                $run['queued_ai']++;
            } else {
                $run['short']++;
            }
            return;
        }

        $changed = $row['title'] !== $data['title'] || $row['description'] !== $data['description'] || $row['duration_iso8601'] !== $durationIso;
        if ($changed) {
            $sql = 'UPDATE videos SET title=:title, description=:description, published_at=:published_at, duration_seconds=:duration_seconds, duration_iso8601=:duration_iso8601, thumbnail_default=:thumbnail_default, thumbnail_medium=:thumbnail_medium, thumbnail_high=:thumbnail_high, thumbnail_maxres=:thumbnail_maxres, url=:url, embed_url=:embed_url, is_long_video=:is_long_video, source_payload_json=:source_payload_json, last_synced_at=:last_synced_at, updated_at=:updated_at WHERE youtube_video_id=:youtube_video_id';
            $this->pdo->prepare($sql)->execute($data);
            $run['updated']++;
            if ($isLong) {
                $this->queueAiJob($videoId, 'reclassify');
                $run['queued_ai']++;
            }
        }
    }

    private function queueAiJob(string $videoId, string $type): void
    {
        $sql = 'INSERT INTO ai_jobs (video_id, job_type, input_hash, request_payload, status, attempts, created_at, updated_at) SELECT id, :type, :hash, :payload, "queued", 0, :now, :now FROM videos WHERE youtube_video_id = :video_id LIMIT 1';
        $this->pdo->prepare($sql)->execute([
            'type' => $type,
            'hash' => sha1($videoId . $type . time()),
            'payload' => '{}',
            'now' => gmdate('Y-m-d H:i:s'),
            'video_id' => $videoId,
        ]);
    }

    private function iso8601ToSeconds(string $duration): int
    {
        $interval = new \DateInterval($duration);
        return ($interval->d * 86400) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
    }
}
