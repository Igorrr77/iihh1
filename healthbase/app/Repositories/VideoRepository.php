<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class VideoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function latestPublic(int $limit = 12): array
    {
        $sql = 'SELECT * FROM videos WHERE is_public = 1 AND is_long_video = 1 ORDER BY published_at DESC LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM videos WHERE youtube_video_id = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    public function findByCategory(string $slug, int $limit = 20): array
    {
        $sql = 'SELECT v.* FROM videos v
            INNER JOIN categories c ON c.id = COALESCE(v.final_primary_category_id, v.ai_primary_category_id)
            WHERE c.slug = :slug AND v.is_public = 1 AND v.is_long_video = 1
            ORDER BY v.published_at DESC LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':slug', $slug);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function search(string $query): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM videos WHERE is_public = 1 AND is_long_video = 1 AND (title LIKE :q OR description LIKE :q OR ai_summary LIKE :q) ORDER BY published_at DESC LIMIT 50');
        $stmt->execute(['q' => '%' . $query . '%']);
        return $stmt->fetchAll();
    }

    public function relatedForVideo(int $videoId, ?int $categoryId, int $limit = 6): array
    {
        if ($categoryId) {
            $stmt = $this->pdo->prepare('SELECT * FROM videos WHERE id != :id AND is_public = 1 AND is_long_video = 1 AND COALESCE(final_primary_category_id, ai_primary_category_id) = :category ORDER BY published_at DESC LIMIT :limit');
            $stmt->bindValue(':id', $videoId, PDO::PARAM_INT);
            $stmt->bindValue(':category', $categoryId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $related = $stmt->fetchAll();
            if (count($related) >= 3) {
                return $related;
            }
        }

        $stmt = $this->pdo->prepare('SELECT * FROM videos WHERE id != :id AND is_public = 1 AND is_long_video = 1 ORDER BY published_at DESC LIMIT :limit');
        $stmt->bindValue(':id', $videoId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(3, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
