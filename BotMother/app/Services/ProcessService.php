<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Core\Database;

final class ProcessService
{
    public function __construct(private readonly Container $container)
    {
    }

    public function create(array $payload, array $scope): array
    {
        $pdo = $this->container->get(Database::class)->pdo();
        $stmt = $pdo->prepare('INSERT INTO processes (account_id, project_id, bot_id, name, slug, description, status, start_mode, created_by, created_at, updated_at) VALUES (:account_id,:project_id,:bot_id,:name,:slug,:description,"draft",:start_mode,:created_by,NOW(),NOW())');
        $stmt->execute([
            'account_id' => $scope['account_id'],
            'project_id' => (int)($payload['project_id'] ?? 0),
            'bot_id' => (int)($payload['bot_id'] ?? 0),
            'name' => $payload['name'] ?? 'Untitled process',
            'slug' => $payload['slug'] ?? ('process-' . time()),
            'description' => $payload['description'] ?? null,
            'start_mode' => $payload['start_mode'] ?? 'triggered',
            'created_by' => $scope['user_id'],
        ]);

        $processId = (int)$pdo->lastInsertId();

        $graph = [
            'schema_version' => '1.0.0',
            'process_meta' => ['name' => $payload['name'] ?? 'Untitled process'],
            'editor' => ['zoom' => 1, 'offset_x' => 0, 'offset_y' => 0, 'grid_enabled' => true],
            'nodes' => [],
            'edges' => [],
            'comments' => [],
            'groups' => [],
        ];

        $stmt = $pdo->prepare('INSERT INTO process_versions (process_id, version_number, status, graph_json, graph_hash, validation_status, created_by, created_at, updated_at) VALUES (:process_id,1,"draft",:graph_json,:graph_hash,"invalid",:created_by,NOW(),NOW())');
        $stmt->execute([
            'process_id' => $processId,
            'graph_json' => json_encode($graph, JSON_UNESCAPED_UNICODE),
            'graph_hash' => hash('sha256', json_encode($graph)),
            'created_by' => $scope['user_id'],
        ]);

        return ['status' => 'created', 'process_id' => $processId, 'version_id' => (int)$pdo->lastInsertId()];
    }

    public function list(array $scope): array
    {
        $pdo = $this->container->get(Database::class)->pdo();
        $stmt = $pdo->prepare('SELECT * FROM processes WHERE account_id=:account_id ORDER BY id DESC');
        $stmt->execute(['account_id' => $scope['account_id']]);
        return ['data' => $stmt->fetchAll()];
    }
}
