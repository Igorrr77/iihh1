<?php

declare(strict_types=1);

namespace App\Repositories;

final class TemplateRepository extends BaseRepository
{
    public function messageTemplates(int $accountId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM message_templates WHERE account_id=:account_id ORDER BY id DESC');
        $stmt->execute(['account_id' => $accountId]);
        return $stmt->fetchAll();
    }

    public function createMessageTemplate(array $data): array
    {
        $stmt = $this->pdo()->prepare('INSERT INTO message_templates (account_id, project_id, name, slug, template_type, description, status, created_by, created_at, updated_at) VALUES (:account_id,:project_id,:name,:slug,:template_type,:description,:status,:created_by,NOW(),NOW())');
        $stmt->execute([
            'account_id' => $data['account_id'],
            'project_id' => $data['project_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'template_type' => $data['template_type'] ?? 'text',
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'created_by' => $data['created_by'],
        ]);
        $id = (int)$this->pdo()->lastInsertId();

        $version = $this->pdo()->prepare('INSERT INTO message_template_versions (template_id, version_number, payload_json, status, created_by, created_at, updated_at) VALUES (:template_id,1,:payload_json,"draft",:created_by,NOW(),NOW())');
        $version->execute([
            'template_id' => $id,
            'payload_json' => json_encode($data['payload_json'] ?? ['type' => 'text', 'text' => ''], JSON_UNESCAPED_UNICODE),
            'created_by' => $data['created_by'],
        ]);

        $this->pdo()->prepare('UPDATE message_templates SET active_version_id=:version_id WHERE id=:id')->execute(['version_id' => (int)$this->pdo()->lastInsertId(), 'id' => $id]);

        return $this->template($id) ?? [];
    }

    public function template(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM message_templates WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function reusableBlocks(int $accountId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM reusable_blocks WHERE account_id=:account_id ORDER BY id DESC');
        $stmt->execute(['account_id' => $accountId]);
        return $stmt->fetchAll();
    }

    public function createReusableBlock(array $data): array
    {
        $stmt = $this->pdo()->prepare('INSERT INTO reusable_blocks (account_id, project_id, name, slug, description, status, created_by, created_at, updated_at) VALUES (:account_id,:project_id,:name,:slug,:description,:status,:created_by,NOW(),NOW())');
        $stmt->execute([
            'account_id' => $data['account_id'],
            'project_id' => $data['project_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'created_by' => $data['created_by'],
        ]);
        $id = (int)$this->pdo()->lastInsertId();

        $version = $this->pdo()->prepare('INSERT INTO reusable_block_versions (reusable_block_id, version_number, graph_json, compiled_graph_json, input_contract_json, output_contract_json, status, created_by, created_at, updated_at) VALUES (:reusable_block_id,1,:graph_json,:compiled_graph_json,:input_contract_json,:output_contract_json,"draft",:created_by,NOW(),NOW())');
        $version->execute([
            'reusable_block_id' => $id,
            'graph_json' => json_encode($data['graph_json'] ?? ['nodes' => [], 'edges' => []], JSON_UNESCAPED_UNICODE),
            'compiled_graph_json' => json_encode($data['compiled_graph_json'] ?? null, JSON_UNESCAPED_UNICODE),
            'input_contract_json' => json_encode($data['input_contract_json'] ?? null, JSON_UNESCAPED_UNICODE),
            'output_contract_json' => json_encode($data['output_contract_json'] ?? null, JSON_UNESCAPED_UNICODE),
            'created_by' => $data['created_by'],
        ]);

        $this->pdo()->prepare('UPDATE reusable_blocks SET active_version_id=:version_id WHERE id=:id')->execute(['version_id' => (int)$this->pdo()->lastInsertId(), 'id' => $id]);

        return $this->block($id) ?? [];
    }

    public function block(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM reusable_blocks WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function exportMessageTemplate(int $id): ?array
    {
        $template = $this->template($id);
        if (!$template) return null;

        $stmt = $this->pdo()->prepare('SELECT * FROM message_template_versions WHERE template_id=:template_id ORDER BY version_number DESC');
        $stmt->execute(['template_id' => $id]);

        return [
            'entity' => 'message_template',
            'template' => $template,
            'versions' => $stmt->fetchAll(),
        ];
    }

    public function importMessageTemplate(int $accountId, int $userId, array $package): array
    {
        $name = (string)($package['template']['name'] ?? 'Imported Template');
        $slug = (string)($package['template']['slug'] ?? ('imported-template-' . time()));
        $versions = $package['versions'] ?? [];

        $created = $this->createMessageTemplate([
            'account_id' => $accountId,
            'project_id' => $package['template']['project_id'] ?? null,
            'name' => $name,
            'slug' => $slug,
            'template_type' => $package['template']['template_type'] ?? 'text',
            'description' => $package['template']['description'] ?? null,
            'status' => 'draft',
            'created_by' => $userId,
            'payload_json' => json_decode((string)($versions[0]['payload_json'] ?? '{}'), true) ?: ['type' => 'text', 'text' => ''],
        ]);

        return $created;
    }

}