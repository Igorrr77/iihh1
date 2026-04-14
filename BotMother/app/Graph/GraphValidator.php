<?php

declare(strict_types=1);

namespace App\Graph;

final class GraphValidator
{
    public function validate(array $graph): array
    {
        $errors = [];
        $nodes = $graph['nodes'] ?? [];
        $edges = $graph['edges'] ?? [];

        if (empty($nodes)) {
            $errors[] = ['code' => 'NO_NODES', 'message' => 'Graph must contain nodes'];
        }

        $nodeIds = [];
        $startFound = false;
        foreach ($nodes as $node) {
            $uuid = $node['uuid'] ?? null;
            if (!$uuid || isset($nodeIds[$uuid])) {
                $errors[] = ['code' => 'NODE_UUID_DUPLICATE', 'message' => 'Node UUID must be unique'];
                continue;
            }
            $nodeIds[$uuid] = true;
            if (str_starts_with((string)($node['type'] ?? ''), 'start') || ($node['type'] ?? '') === 'start') {
                $startFound = true;
            }
        }

        if (!$startFound) {
            $errors[] = ['code' => 'NO_START_NODE', 'message' => 'At least one trigger/start node required'];
        }

        $edgeIds = [];
        foreach ($edges as $edge) {
            $eUuid = $edge['uuid'] ?? null;
            if (!$eUuid || isset($edgeIds[$eUuid])) {
                $errors[] = ['code' => 'EDGE_UUID_DUPLICATE', 'message' => 'Edge UUID must be unique'];
            }
            $edgeIds[$eUuid] = true;
            $from = $edge['from']['node_uuid'] ?? '';
            $to = $edge['to']['node_uuid'] ?? '';
            if (!isset($nodeIds[$from]) || !isset($nodeIds[$to])) {
                $errors[] = ['code' => 'DANGLING_EDGE', 'message' => 'Edge references missing node'];
            }
        }

        return [
            'status' => empty($errors) ? 'valid' : 'invalid',
            'errors' => $errors,
            'warnings' => [],
        ];
    }
}
