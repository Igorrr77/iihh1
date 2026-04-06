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
        $categoryId = (int)($video['final_primary_category_id'] ?: $video['ai_primary_category_id']);
        $related = (new VideoRepository($this->db()))->relatedForVideo((int)$video['id'], $categoryId ?: null, 6);
        Response::view('public/video', compact('video', 'related'));
    }
}
