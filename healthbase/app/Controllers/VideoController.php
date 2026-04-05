<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\VideoRepository;

class VideoController extends BaseController
{
    public function index(Request $request): void
    {
        $query = trim((string)$request->input('q', ''));
        $videos = $query
            ? (new VideoRepository($this->db()))->search($query)
            : (new VideoRepository($this->db()))->latestPublic(120);
        Response::view('public/videos', compact('videos', 'query'));
    }

    public function show(Request $request, array $params): void
    {
        $video = (new VideoRepository($this->db()))->findBySlug($params['slug']);
        if (!$video) {
            Response::view('errors/404', [], 404);
            return;
        }
        $related = [];
        if (!empty($video['final_primary_category_id'])) {
            $stmt = $this->db()->prepare('SELECT slug FROM categories WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => (int)$video['final_primary_category_id']]);
            $slug = $stmt->fetchColumn();
            if ($slug) {
                $related = (new VideoRepository($this->db()))->findByCategory((string)$slug, 6);
                $related = array_values(array_filter($related, static fn(array $r): bool => $r['id'] !== $video['id']));
            }
        }
        Response::view('public/video', compact('video', 'related'));
    }
}
