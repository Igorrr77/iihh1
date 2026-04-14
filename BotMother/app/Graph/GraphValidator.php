<?php

declare(strict_types=1);

namespace App\Graph;

final class GraphValidator
{
    public function validate(array $graph): array
    {
        $errors = [];
        $warnings = [];
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

            $type = (string)($node['type'] ?? '');
            $config = $node['config'] ?? [];
            if ($type === 'http_request' && empty($config['url'])) {
                $errors[] = ['code' => 'HTTP_REQUEST_URL_REQUIRED', 'message' => 'http_request node requires config.url'];
            }
            if ($type === 'condition' && empty($config['left_key'])) {
                $warnings[] = ['code' => 'CONDITION_LEFT_KEY_DEFAULTED', 'message' => 'condition.left_key is empty; default "input" will be used'];
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

        foreach ($nodes as $node) {
            $type = (string)($node['type'] ?? '');
            if ($type !== 'condition') {
                continue;
            }
            $uuid = (string)($node['uuid'] ?? '');
            $ports = [];
            foreach ($edges as $edge) {
                if (($edge['from']['node_uuid'] ?? '') !== $uuid) {
                    continue;
                }
                $ports[] = (string)($edge['from']['port'] ?? 'next');
            }
            $normalized = array_values(array_unique(array_map(fn (string $p): string => strtolower($p), $ports)));
            $hasTrue = in_array('true', $normalized, true) || in_array('yes', $normalized, true) || in_array('success', $normalized, true);
            $hasFalse = in_array('false', $normalized, true) || in_array('no', $normalized, true) || in_array('fail', $normalized, true) || in_array('error', $normalized, true);
            if (!$hasTrue || !$hasFalse) {
                $errors[] = ['code' => 'CONDITION_BRANCH_MISSING', 'message' => 'condition node must have both true/success and false/fail branches', 'node_uuid' => $uuid];
            }
        }

        return [
            'status' => empty($errors) ? 'valid' : 'invalid',
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
