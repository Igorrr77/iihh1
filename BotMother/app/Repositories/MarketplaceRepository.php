<?php

declare(strict_types=1);

namespace App\Repositories;

final class MarketplaceRepository extends BaseRepository
{
    public function items(): array
    {
        return $this->pdo()->query('SELECT * FROM marketplace_items WHERE status="published" ORDER BY id DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM marketplace_items WHERE id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function install(int $itemId, int $accountId, ?int $projectId = null): array
    {
        $item = $this->find($itemId);
        if (!$item) {
            return ['error' => 'item_not_found'];
        }

        $versionStmt = $this->pdo()->prepare('SELECT * FROM marketplace_item_versions WHERE marketplace_item_id=:id AND status="published" ORDER BY version_number DESC LIMIT 1');
        $versionStmt->execute(['id' => $itemId]);
        $version = $versionStmt->fetch();
        if (!$version) {
            return ['error' => 'version_not_found'];
        }

        $stmt = $this->pdo()->prepare('INSERT INTO marketplace_installs (marketplace_item_id, marketplace_item_version_id, account_id, project_id, installed_entity_type, installed_entity_id, installed_at, created_at) VALUES (:item_id,:version_id,:account_id,:project_id,:entity_type,:entity_id,NOW(),NOW())');
        $stmt->execute([
            'item_id' => $itemId,
            'version_id' => $version['id'],
            'account_id' => $accountId,
            'project_id' => $projectId,
            'entity_type' => $item['item_type'],
            'entity_id' => $item['source_entity_id'],
        ]);

        $this->pdo()->prepare('UPDATE marketplace_items SET install_count = install_count + 1, updated_at=NOW() WHERE id=:id')->execute(['id' => $itemId]);

        return ['status' => 'installed', 'item_id' => $itemId, 'version_id' => (int)$version['id']];
    }
}
