<?php

declare(strict_types=1);

namespace App\Repositories;

final class FunnelRepository extends BaseRepository
{
    public function list(int $projectId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM funnels WHERE project_id=:project_id ORDER BY id DESC');
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo()->prepare('INSERT INTO funnels (account_id, project_id, bot_id, name, slug, description, status, created_by, created_at, updated_at) VALUES (:account_id,:project_id,:bot_id,:name,:slug,:description,:status,:created_by,NOW(),NOW())');
        $stmt->execute([
            'account_id' => $data['account_id'],
            'project_id' => $data['project_id'],
            'bot_id' => $data['bot_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'created_by' => $data['created_by'],
        ]);
        return $this->find((int)$this->pdo()->lastInsertId()) ?? [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM funnels WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function analytics(int $funnelId): array
    {
        $entries = (int)$this->pdo()->query('SELECT COUNT(*) FROM funnel_entries WHERE funnel_id=' . (int)$funnelId)->fetchColumn();
        $completed = (int)$this->pdo()->query('SELECT COUNT(*) FROM funnel_entries WHERE funnel_id=' . (int)$funnelId . ' AND status="completed"')->fetchColumn();
        $dropoffs = (int)$this->pdo()->query('SELECT COUNT(*) FROM funnel_entries WHERE funnel_id=' . (int)$funnelId . ' AND status="dropped"')->fetchColumn();

        $conversion = $entries > 0 ? round($completed / $entries, 4) : 0.0;
        return [
            'entries' => $entries,
            'completed' => $completed,
            'dropoffs' => $dropoffs,
            'conversion' => $conversion,
        ];
    }
}
