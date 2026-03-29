<?php

declare(strict_types=1);

namespace App\Services;

final class ScenarioMacroCompiler
{
    public function compile(array $scenario): array
    {
        $events = $scenario['events'] ?? [];
        $compiled = [];

        foreach ($events as $event) {
            $type = (string) ($event['type'] ?? '');
            if ($type !== 'macro_repeat_message') {
                $compiled[] = $event;
                continue;
            }

            $times = (int) (($event['payload']['times'] ?? 1));
            $startAt = (int) (($event['at'] ?? 0));
            $step = (int) (($event['payload']['step_sec'] ?? 15));
            $text = (string) (($event['payload']['text'] ?? 'Auto message'));

            for ($i = 0; $i < $times; $i++) {
                $compiled[] = [
                    'at' => $startAt + $i * $step,
                    'type' => 'chat_message',
                    'payload' => ['name' => 'System', 'text' => $text],
                ];
            }
        }

        usort($compiled, static fn(array $a, array $b): int => (int) ($a['at'] ?? 0) <=> (int) ($b['at'] ?? 0));

        return ['events' => $compiled];
    }

    public function diff(array $left, array $right): array
    {
        $l = $left['events'] ?? [];
        $r = $right['events'] ?? [];

        return [
            'left_count' => count($l),
            'right_count' => count($r),
            'delta' => count($r) - count($l),
        ];
    }
}
