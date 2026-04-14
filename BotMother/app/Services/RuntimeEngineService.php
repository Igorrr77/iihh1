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

        $current = $entry['node_uuid'];
        $steps = 0;

        while ($current && $steps < 500) {
            $steps++;
            $node = $compiled['nodes'][$current] ?? null;
            if (!$node) {
                break;
            }

            $nodeType = (string)$node['type'];
            $this->executions->step($executionId, $context['process_version_id'], $current, $nodeType, 'entered');

            if ($nodeType === 'send_text') {
                $text = (string)($node['config']['text'] ?? '');
                $res = $telegram->call('sendMessage', ['chat_id' => $context['telegram_chat_id'], 'text' => $text, 'parse_mode' => 'HTML']);
                $this->executions->step($executionId, $context['process_version_id'], $current, $nodeType, ($res['ok'] ?? false) ? 'completed' : 'failed', [], $res);
            } elseif ($nodeType === 'wait_input') {
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
            } else {
                $this->executions->step($executionId, $context['process_version_id'], $current, $nodeType, 'completed');
            }

            $next = $node['next'][0]['target'] ?? null;
            $current = is_string($next) && $next !== '' ? $next : null;
        }

        $this->executions->setStatus($executionId, 'completed', $current);
    }
}
