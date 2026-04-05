<?php

declare(strict_types=1);

$taxonomy = require root_path('config/taxonomy.php');
$pdo = $GLOBALS['pdo'];

$stmt = $pdo->prepare('INSERT INTO categories (slug,parent_id,title,description,sort_order,is_active,is_system,created_at,updated_at) VALUES (:slug,:parent_id,:title,:description,:sort_order,1,1,:created_at,:updated_at) ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), sort_order=VALUES(sort_order), updated_at=VALUES(updated_at)');

$now = gmdate('Y-m-d H:i:s');
foreach ($taxonomy as $item) {
    $stmt->execute([
        'slug' => $item['slug'],
        'parent_id' => $item['parent_id'] ?? null,
        'title' => $item['title'],
        'description' => $item['description'] ?? null,
        'sort_order' => $item['sort_order'] ?? 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}
