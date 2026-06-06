<?php

declare(strict_types=1);

namespace Commentor;

final class WebhookNormalizer
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function normalize(array $payload): array
    {
        if (isset($payload['object'], $payload['entry']) && is_array($payload['entry'])) {
            return self::normalizeMeta($payload);
        }

        return [self::normalizeSimple($payload)];
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeSimple(array $data): array
    {
        return [
            'platform' => (string) ($data['platform'] ?? ''),
            'account_external_id' => (string) ($data['account_external_id'] ?? $data['account_id'] ?? ''),
            'external_comment_id' => (string) ($data['external_comment_id'] ?? ''),
            'external_media_id' => (string) ($data['external_media_id'] ?? ''),
            'commenter_handle' => ltrim((string) ($data['commenter_handle'] ?? ''), '@'),
            'comment_text' => (string) ($data['comment_text'] ?? ''),
            'content_context' => (string) ($data['content_context'] ?? ''),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeMeta(array $payload): array
    {
        $result = [];
        foreach ($payload['entry'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $accountExternalId = (string) ($entry['id'] ?? '');
            $changes = $entry['changes'] ?? [];
            if (!is_array($changes)) {
                continue;
            }

            foreach ($changes as $change) {
                $value = $change['value'] ?? null;
                if (!is_array($value)) {
                    continue;
                }

                $isCommentEvent = isset($value['text']) && (isset($value['id']) || isset($value['comment_id']));
                if (!$isCommentEvent) {
                    continue;
                }

                $platform = self::detectMetaPlatform((string) ($payload['object'] ?? ''), (string) ($change['field'] ?? ''));
                $result[] = [
                    'platform' => $platform,
                    'account_external_id' => $accountExternalId,
                    'external_comment_id' => (string) ($value['id'] ?? $value['comment_id'] ?? ''),
                    'external_media_id' => (string) ($value['media']['id'] ?? $value['post_id'] ?? ''),
                    'commenter_handle' => ltrim((string) ($value['from']['username'] ?? $value['from']['name'] ?? $value['from']['id'] ?? 'user'), '@'),
                    'comment_text' => (string) ($value['text'] ?? ''),
                    'content_context' => (string) ($value['media']['caption'] ?? ''),
                ];
            }
        }

        return $result;
    }

    private static function detectMetaPlatform(string $object, string $field): string
    {
        if ($object === 'instagram') {
            return 'instagram';
        }

        if ($field === 'feed' || $field === 'comments') {
            return 'facebook_page';
        }

        return 'facebook';
    }
}
