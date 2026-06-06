<?php

declare(strict_types=1);

namespace App\Core;

final class SchedulerService
{
    public function __construct(
        private readonly ScheduleRepository $schedules,
        private readonly SourceRepository $sources,
        private readonly Queue $queue,
    ) {
    }

    public function tick(): int
    {
        $count = 0;
        foreach ($this->schedules->due() as $schedule) {
            $source = $this->sources->byId((int) $schedule['source_id']);
            if (!$source || (int) $source['is_active'] !== 1) {
                continue;
            }

            $payload = [
                'source_id' => (int) $source['id'],
                'provider' => (string) $source['provider'],
                'account_handle' => (string) $source['account_handle'],
                'mode' => (string) $source['mode'],
                'query_text' => (string) ($source['query_text'] ?? ''),
                'content_types' => $source['content_types'],
                'filters' => $source['filters'],
            ];

            $this->queue->push('pull_content', $payload);
            $this->schedules->reschedule($schedule);
            $count++;
        }

        return $count;
    }
}
