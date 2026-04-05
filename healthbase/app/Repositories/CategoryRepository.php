<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class CategoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function allActive(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, title ASC');
        return $stmt->fetchAll();
    }

    public function bySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }
}
