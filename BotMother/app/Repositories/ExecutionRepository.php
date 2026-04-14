<?php

declare(strict_types=1);

namespace App\Repositories;

final class ExecutionRepository extends BaseRepository
{
    public function create(array $data): int
    {
        $stmt = $this->pdo()->prepare('INSERT INTO executions (account_id, project_id, bot_id, process_id, process_version_id, contact_id, trigger_type, trigger_ref, trigger_payload_json, current_node_uuid, status, step_count, context_json, started_at, created_at, updated_at) VALUES (:account_id,:project_id,:bot_id,:process_id,:process_version_id,:contact_id,:trigger_type,:trigger_ref,:trigger_payload_json,:current_node_uuid,:status,0,:context_json,NOW(),NOW(),NOW())');
        $stmt->execute([
            'account_id' => $data['account_id'],
            'project_id' => $data['project_id'],
            'bot_id' => $data['bot_id'],
            'process_id' => $data['process_id'],
            'process_version_id' => $data['process_version_id'],
            'contact_id' => $data['contact_id'],
            'trigger_type' => $data['trigger_type'],
            'trigger_ref' => $data['trigger_ref'] ?? null,
            'trigger_payload_json' => json_encode($data['trigger_payload'] ?? [], JSON_UNESCAPED_UNICODE),
            'current_node_uuid' => $data['current_node_uuid'] ?? null,
            'status' => $data['status'] ?? 'running',
            'context_json' => json_encode($data['context'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
        return (int)$this->pdo()->lastInsertId();
    }

    public function step(int $executionId, int $processVersionId, string $nodeUuid, string $nodeType, string $status, array $input = [], array $output = []): void
    {
        $stmt = $this->pdo()->prepare('INSERT INTO execution_steps (execution_id, process_version_id, node_uuid, node_type, status, input_json, output_json, created_at) VALUES (:execution_id,:process_version_id,:node_uuid,:node_type,:status,:input_json,:output_json,NOW())');
        $stmt->execute([
            'execution_id' => $executionId,
            'process_version_id' => $processVersionId,
            'node_uuid' => $nodeUuid,
            'node_type' => $nodeType,
            'status' => $status,
            'input_json' => json_encode($input, JSON_UNESCAPED_UNICODE),
            'output_json' => json_encode($output, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function setStatus(int $executionId, string $status, ?string $currentNode = null): void
    {
        $stmt = $this->pdo()->prepare('UPDATE executions SET status=:status, current_node_uuid=:current_node_uuid, finished_at=IF(:status IN ("completed","failed","cancelled"), NOW(), finished_at), updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $executionId, 'status' => $status, 'current_node_uuid' => $currentNode]);
    }

    public function createWaitingState(array $data): void
    {
        $stmt = $this->pdo()->prepare('INSERT INTO waiting_states (execution_id, account_id, project_id, bot_id, contact_id, node_uuid, input_type, save_to_key, validation_rules_json, timeout_port, invalid_port, success_port, attempt_count, max_attempts, expires_at, status, created_at, updated_at) VALUES (:execution_id,:account_id,:project_id,:bot_id,:contact_id,:node_uuid,:input_type,:save_to_key,:validation_rules_json,:timeout_port,:invalid_port,:success_port,0,:max_attempts,:expires_at,"active",NOW(),NOW())');
        $stmt->execute([
            'execution_id' => $data['execution_id'],
            'account_id' => $data['account_id'],
            'project_id' => $data['project_id'],
            'bot_id' => $data['bot_id'],
            'contact_id' => $data['contact_id'],
            'node_uuid' => $data['node_uuid'],
            'input_type' => $data['input_type'] ?? 'text',
            'save_to_key' => $data['save_to_key'] ?? 'input',
            'validation_rules_json' => json_encode($data['validation_rules'] ?? [], JSON_UNESCAPED_UNICODE),
            'timeout_port' => $data['timeout_port'] ?? 'timeout',
            'invalid_port' => $data['invalid_port'] ?? 'invalid',
            'success_port' => $data['success_port'] ?? 'success',
            'max_attempts' => $data['max_attempts'] ?? 3,
            'expires_at' => $data['expires_at'] ?? null,
        ]);
    }

    public function updateContext(int $executionId, array $context): void
    {
        $stmt = $this->pdo()->prepare('UPDATE executions SET context_json=:context_json, updated_at=NOW() WHERE id=:id');
        $stmt->execute([
            'id' => $executionId,
            'context_json' => json_encode($context, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
