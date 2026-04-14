<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ExecutionRepository;
use App\Telegram\TelegramClient;

final class RuntimeEngineService
{
    public function __construct(private readonly ExecutionRepository $executions)
    {
    }

    public function runCompiled(array $compiled, array $context, TelegramClient $telegram): void
    {
        $entry = $compiled['entrypoints'][0] ?? null;
        if (!$entry) {
            return;
        }

        $executionId = $this->executions->create([
            'account_id' => $context['account_id'],
            'project_id' => $context['project_id'],
            'bot_id' => $context['bot_id'],
            'process_id' => $context['process_id'],
            'process_version_id' => $context['process_version_id'],
            'contact_id' => $context['contact_id'],
            'trigger_type' => $context['trigger_type'],
            'trigger_ref' => $context['trigger_ref'] ?? null,
            'trigger_payload' => $context['trigger_payload'] ?? [],
            'current_node_uuid' => $entry['node_uuid'],
            'status' => 'running',
            'context' => $context,
        ]);

        $this->runLoop($executionId, $compiled, $context, (string)$entry['node_uuid'], $telegram);
    }

    public function resumeCompiled(int $executionId, array $compiled, array $context, string $startNodeUuid, TelegramClient $telegram): void
    {
        $this->runLoop($executionId, $compiled, $context, $startNodeUuid, $telegram);
    }

    private function runLoop(int $executionId, array $compiled, array $context, string $startNodeUuid, TelegramClient $telegram): void
    {
        $current = $startNodeUuid;
        $steps = 0;
        $maxSteps = (int)($compiled['guards']['max_steps'] ?? 500);

        while ($current !== '' && $steps < $maxSteps) {
            $steps++;
            $node = $compiled['nodes'][$current] ?? null;
            if (!$node) {
                break;
            }

            $nodeType = (string)$node['type'];
            $this->executions->step($executionId, $context['process_version_id'], $current, $nodeType, 'entered');

            if ($nodeType === 'send_text') {
                $text = $this->renderText((string)($node['config']['text'] ?? ''), $context);
                $res = $telegram->call('sendMessage', ['chat_id' => $context['telegram_chat_id'], 'text' => $text, 'parse_mode' => 'HTML']);
                $this->executions->step($executionId, $context['process_version_id'], $current, $nodeType, ($res['ok'] ?? false) ? 'completed' : 'failed', [], $res);
            } elseif ($nodeType === 'wait_input') {
                $this->executions->updateContext($executionId, $context);
                $this->executions->createWaitingState([
                    'execution_id' => $executionId,
                    'account_id' => $context['account_id'],
                    'project_id' => $context['project_id'],
                    'bot_id' => $context['bot_id'],
                    'contact_id' => $context['contact_id'],
                    'node_uuid' => $current,
                    'input_type' => $node['config']['input_type'] ?? 'text',
                    'save_to_key' => $node['config']['save_to'] ?? 'input',
                    'max_attempts' => (int)($node['config']['max_attempts'] ?? 3),
                    'expires_at' => isset($node['config']['timeout_seconds']) ? date('Y-m-d H:i:s', time() + (int)$node['config']['timeout_seconds']) : null,
                ]);
                $this->executions->step($executionId, $context['process_version_id'], $current, $nodeType, 'waiting');
                $this->executions->setStatus($executionId, 'waiting', $current);
                return;
            } elseif ($nodeType === 'condition') {
                $result = $this->evaluateCondition($node['config'] ?? [], $context);
                $this->executions->step($executionId, $context['process_version_id'], $current, $nodeType, 'completed', [], ['result' => $result]);
                $this->executions->updateContext($executionId, $context);
                $next = $this->resolveNext($node, $result ? 'true' : 'false');
                $current = is_string($next) && $next !== '' ? $next : '';
                continue;
            } elseif ($nodeType === 'delay') {
                $seconds = max(0, min(5, (int)($node['config']['seconds'] ?? 0)));
                if ($seconds > 0) {
                    sleep($seconds);
                }
                $this->executions->step($executionId, $context['process_version_id'], $current, $nodeType, 'completed', [], ['seconds' => $seconds]);
            } elseif ($nodeType === 'http_request') {
                $result = $this->performHttpRequest($node['config'] ?? [], $context);
                $saveTo = (string)($node['config']['save_to'] ?? 'http_response');
                $context['vars'][$saveTo] = $result['body'] ?? null;
                $this->executions->step($executionId, $context['process_version_id'], $current, $nodeType, $result['ok'] ? 'completed' : 'failed', [], $result);
                $this->executions->updateContext($executionId, $context);
                $next = $this->resolveNext($node, $result['ok'] ? 'success' : 'fail');
                $current = is_string($next) && $next !== '' ? $next : '';
                continue;
            } else {
                $this->executions->step($executionId, $context['process_version_id'], $current, $nodeType, 'completed');
            }

            $this->executions->updateContext($executionId, $context);
            $next = $this->resolveNext($node);
            $current = is_string($next) && $next !== '' ? $next : '';
        }

        $this->executions->updateContext($executionId, $context);
        $this->executions->setStatus($executionId, 'completed', $current);
    }

    private function resolveNext(array $node, ?string $preferredPort = null): ?string
    {
        $preferredPorts = $this->expandPortAliases($preferredPort);
        foreach ($node['next'] ?? [] as $edge) {
            if ($preferredPort === null || in_array((string)($edge['port'] ?? ''), $preferredPorts, true)) {
                return (string)($edge['target'] ?? '');
            }
        }
        return $node['next'][0]['target'] ?? null;
    }

    private function evaluateCondition(array $config, array $context): bool
    {
        $leftKey = (string)($config['left_key'] ?? 'input');
        $operator = (string)($config['operator'] ?? 'eq');
        $right = $config['right_value'] ?? null;
        if (is_string($right)) {
            $right = $this->renderText($right, $context);
        }
        $left = $context['vars'][$leftKey] ?? $context[$leftKey] ?? null;

        return match ($operator) {
            'neq' => (string)$left !== (string)$right,
            'contains' => str_contains((string)$left, (string)$right,
            ),
            'gt' => (float)$left > (float)$right,
            'lt' => (float)$left < (float)$right,
            default => (string)$left === (string)$right,
        };
    }

    private function performHttpRequest(array $config, array $context): array
    {
        $url = (string)($config['url'] ?? '');
        $url = $this->renderText($url, $context);
        if ($url === '') {
            return ['ok' => false, 'error' => 'url_required'];
        }

        $method = strtoupper((string)($config['method'] ?? 'GET'));
        $timeout = max(1, min(15, (int)($config['timeout_seconds'] ?? 5)));
        $headers = $config['headers'] ?? [];
        $body = $config['body'] ?? null;
        $content = null;
        if ($body !== null) {
            if (is_string($body)) {
                $body = $this->renderText($body, $context);
            }
            $content = is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE) : (string)$body;
            if (is_array($headers)) {
                $headers[] = 'Content-Type: application/json';
            }
        }

        $options = [
            'http' => [
                'method' => $method,
                'timeout' => $timeout,
                'ignore_errors' => true,
                'header' => is_array($headers) ? implode("\r\n", $headers) : (string)$headers,
                'content' => $content,
            ],
        ];

        $raw = @file_get_contents($url, false, stream_context_create($options));
        $status = 0;
        foreach ($http_response_header ?? [] as $headerLine) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $headerLine, $m)) {
                $status = (int)$m[1];
                break;
            }
        }

        $decoded = json_decode((string)$raw, true);
        return [
            'ok' => $status >= 200 && $status < 300,
            'status_code' => $status,
            'body' => is_array($decoded) ? $decoded : (string)$raw,
        ];
    }

    private function expandPortAliases(?string $preferredPort): array
    {
        if ($preferredPort === null || $preferredPort === '') {
            return [];
        }

        return match ($preferredPort) {
            'true' => ['true', 'yes', 'success'],
            'false' => ['false', 'no', 'fail', 'failure', 'error'],
            'success' => ['success', 'ok', 'true', 'yes'],
            'fail' => ['fail', 'failure', 'error', 'false', 'no'],
            default => [$preferredPort],
        };
    }

    private function renderText(string $text, array $context): string
    {
        return (string)preg_replace_callback('/\{\{([\w\.\-]+)\}\}/', function (array $m) use ($context): string {
            $key = $m[1] ?? '';
            return (string)($context['vars'][$key] ?? $context[$key] ?? '');
        }, $text);
    }
}
