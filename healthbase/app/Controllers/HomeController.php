<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\VideoRepository;

class HomeController extends BaseController
{
    public function index(Request $request): void
    {
        $videos = (new VideoRepository($this->db()))->latestPublic(12);
        $categories = (new CategoryRepository($this->db()))->allActive();
        Response::view('public/home', compact('videos', 'categories'));
    }

    public function start(Request $request): void
    {
        $videos = (new VideoRepository($this->db()))->latestPublic(24);
        Response::view('public/start', compact('videos'));
    }

    public function topics(Request $request): void
    {
        $categories = (new CategoryRepository($this->db()))->allActive();
        Response::view('public/topics', compact('categories'));
    }

    public function category(Request $request, array $params): void
    {
        $slug = $params['category'];
        $repo = new CategoryRepository($this->db());
        $category = $repo->bySlug($slug);
        if (!$category) {
            Response::view('errors/404', [], 404);
            return;
        }
        $videos = (new VideoRepository($this->db()))->findByCategory($slug, 30);
        Response::view('public/category', compact('category', 'videos'));
    }

    public function subcategory(Request $request, array $params): void
    {
        $repo = new CategoryRepository($this->db());
        $parent = $repo->bySlug($params['category']);
        $subcategory = $repo->bySlug($params['subcategory']);
        if (!$parent || !$subcategory) {
            Response::view('errors/404', [], 404);
            return;
        }
        $videos = (new VideoRepository($this->db()))->findByCategory($subcategory['slug'], 30);
        Response::view('public/subcategory', compact('parent', 'subcategory', 'videos'));
    }
}
