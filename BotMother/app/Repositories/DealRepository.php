<?php

declare(strict_types=1);

namespace App\Repositories;

final class DealRepository extends BaseRepository
{
    public function createPipeline(array $data): array
    {
        $stmt = $this->pdo()->prepare('INSERT INTO pipelines (account_id, project_id, name, slug, description, status, created_by, created_at, updated_at) VALUES (:account_id,:project_id,:name,:slug,:description,:status,:created_by,NOW(),NOW())');
        $stmt->execute([
            'account_id' => $data['account_id'],
            'project_id' => $data['project_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => $data['created_by'],
        ]);
        return $this->pipeline((int)$this->pdo()->lastInsertId()) ?? [];
    }

    public function pipelines(int $projectId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM pipelines WHERE project_id=:project_id ORDER BY id DESC');
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    public function pipeline(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM pipelines WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function createDeal(array $data): array
    {
        $stmt = $this->pdo()->prepare('INSERT INTO deals (account_id, project_id, bot_id, contact_id, pipeline_id, stage_id, title, amount, currency, status, manager_id, created_by, created_at, updated_at) VALUES (:account_id,:project_id,:bot_id,:contact_id,:pipeline_id,:stage_id,:title,:amount,:currency,:status,:manager_id,:created_by,NOW(),NOW())');
        $stmt->execute([
            'account_id' => $data['account_id'],
            'project_id' => $data['project_id'],
            'bot_id' => $data['bot_id'] ?? null,
            'contact_id' => $data['contact_id'],
            'pipeline_id' => $data['pipeline_id'],
            'stage_id' => $data['stage_id'],
            'title' => $data['title'],
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'status' => $data['status'] ?? 'open',
            'manager_id' => $data['manager_id'] ?? null,
            'created_by' => $data['created_by'],
        ]);
        return $this->deal((int)$this->pdo()->lastInsertId()) ?? [];
    }

    public function deals(int $projectId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM deals WHERE project_id=:project_id ORDER BY id DESC');
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    public function deal(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM deals WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function moveStage(int $dealId, int $stageId, int $userId): ?array
    {
        $deal = $this->deal($dealId);
        if (!$deal) return null;

        $stmt = $this->pdo()->prepare('UPDATE deals SET stage_id=:stage_id, updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id' => $dealId, 'stage_id' => $stageId]);

        $history = $this->pdo()->prepare('INSERT INTO deal_status_history (deal_id, from_stage_id, to_stage_id, changed_by, created_at) VALUES (:deal_id,:from_stage_id,:to_stage_id,:changed_by,NOW())');
        $history->execute(['deal_id' => $dealId, 'from_stage_id' => $deal['stage_id'], 'to_stage_id' => $stageId, 'changed_by' => $userId]);

        return $this->deal($dealId);
    }

    public function addNote(int $dealId, int $userId, string $text): void
    {
        $stmt = $this->pdo()->prepare('INSERT INTO deal_notes (deal_id, author_user_id, note_text, created_at, updated_at) VALUES (:deal_id,:author_user_id,:note_text,NOW(),NOW())');
        $stmt->execute(['deal_id' => $dealId, 'author_user_id' => $userId, 'note_text' => $text]);
    }

    public function addTask(int $dealId, int $userId, string $title, ?string $dueAt = null): void
    {
        $stmt = $this->pdo()->prepare('INSERT INTO deal_tasks (deal_id, assigned_user_id, title, due_at, status, created_by, created_at, updated_at) VALUES (:deal_id,:assigned_user_id,:title,:due_at,"open",:created_by,NOW(),NOW())');
        $stmt->execute(['deal_id' => $dealId, 'assigned_user_id' => $userId, 'title' => $title, 'due_at' => $dueAt, 'created_by' => $userId]);
    }
}
