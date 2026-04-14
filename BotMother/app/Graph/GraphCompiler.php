<?php

declare(strict_types=1);

namespace App\Graph;

final class GraphCompiler
{
    public function compile(array $graph): array
    {
        $compiled = [
            'version' => '1.0.0',
            'entrypoints' => [],
            'nodes' => [],
            'guards' => [
                'max_steps' => 500,
                'max_same_node_hits' => 20,
            ],
        ];

        foreach ($graph['nodes'] ?? [] as $node) {
            $uuid = $node['uuid'];
            $compiled['nodes'][$uuid] = [
                'type' => $node['type'] ?? 'unknown',
                'config' => $node['config'] ?? [],
                'next' => [],
            ];
            if (str_starts_with((string)($node['type'] ?? ''), 'start') || ($node['type'] ?? '') === 'start') {
                $compiled['entrypoints'][] = [
                    'trigger_type' => $node['config']['trigger_type'] ?? 'manual',
                    'key' => $node['config']['command'] ?? 'default',
                    'node_uuid' => $uuid,
                ];
            }
        }

        foreach ($graph['edges'] ?? [] as $edge) {
            $from = $edge['from']['node_uuid'] ?? null;
            if (!$from || !isset($compiled['nodes'][$from])) {
                continue;
            }
            $compiled['nodes'][$from]['next'][] = [
                'port' => $edge['from']['port'] ?? 'next',
                'target' => $edge['to']['node_uuid'] ?? '',
                'condition_key' => $edge['condition_key'] ?? null,
            ];
        }

        $compiled['hash'] = hash('sha256', json_encode($compiled));

        return $compiled;
    }
}
