<?php

declare(strict_types=1);

namespace App\Services;

final class ScenarioService
{
    /** @var string[] */
    private array $allowedTypes = [
        'video_start',
        'chat_message',
        'offer_popup',
        'poll',
        'redirect',
        'ai_reply',
        'data_slice',
    ];

    public function validate(array $scenario): array
    {
        $events = $scenario['events'] ?? null;
        if (!is_array($events)) {
            return ['ok' => false, 'message' => 'В сценарии отсутствует массив events'];
        }

        $errors = [];
        foreach ($events as $idx => $event) {
            if (!is_array($event)) {
                $errors[] = ['index' => $idx, 'error' => 'event must be object'];
                continue;
            }

            $at = $event['at'] ?? null;
            $type = $event['type'] ?? null;
            if (!is_int($at) || $at < 0) {
                $errors[] = ['index' => $idx, 'error' => 'at must be non-negative int'];
            }
            if (!is_string($type) || !in_array($type, $this->allowedTypes, true)) {
                $errors[] = ['index' => $idx, 'error' => 'unsupported event type'];
            }
            if (isset($event['payload']) && !is_array($event['payload'])) {
                $errors[] = ['index' => $idx, 'error' => 'payload must be object'];
            }
        }

        return $errors === [] ? ['ok' => true, 'message' => 'Scenario schema valid'] : ['ok' => false, 'errors' => $errors];
    }

    public function importFromJson(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'Некорректный JSON'];
        }

        return $this->validate($data);
    }

    public function migrateLegacyScenario(array $legacy): array
    {
        $events = [];
        foreach (($legacy['timeline'] ?? []) as $item) {
            $events[] = [
                'at' => max(0, (int) ($item['sec'] ?? 0)),
                'type' => (string) ($item['kind'] ?? 'chat_message'),
                'payload' => (array) ($item['data'] ?? []),
            ];
        }

        return [
            'schema_version' => 2,
            'events' => $events,
        ];
    }

    public function importAdapter(string $adapter, array $payload): array
    {
        return match ($adapter) {
            'legacy_v1' => $this->migrateLegacyScenario($payload),
            'native' => $payload,
            default => ['schema_version' => 2, 'events' => []],
        };
    }

    public function exportAdapter(string $adapter, array $scenario): array
    {
        if ($adapter === 'legacy_v1') {
            $timeline = [];
            foreach (($scenario['events'] ?? []) as $event) {
                $timeline[] = [
                    'sec' => (int) ($event['at'] ?? 0),
                    'kind' => (string) ($event['type'] ?? 'chat_message'),
                    'data' => (array) ($event['payload'] ?? []),
                ];
            }
            return ['timeline' => $timeline];
        }

        return $scenario;
    }

    public function exportTemplate(string $webinarId): array
    {
        return [
            'webinar_id' => $webinarId,
            'timeline_mode' => 'just_in_time',
            'timezone' => 'Europe/Kiev',
            'schema_version' => 2,
            'events' => [
                ['at' => 0, 'type' => 'video_start', 'payload' => ['source' => 'youtube']],
                ['at' => 120, 'type' => 'chat_message', 'payload' => ['name' => 'Ольга', 'text' => 'Супер, продолжаем!']],
                ['at' => 1800, 'type' => 'offer_popup', 'payload' => ['title' => 'Спецпредложение', 'ttl_sec' => 900]],
            ],
        ];
    }
}
