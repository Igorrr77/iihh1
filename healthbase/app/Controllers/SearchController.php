<?php

declare(strict_types=1);

namespace App\Controllers;

use App\AI\GeminiClient;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\VideoRepository;
use App\Services\Logger;
use App\Services\SearchService;

class SearchController extends BaseController
{
    public function index(Request $request): void
    {
        $q = trim((string)$request->input('q', ''));
        $results = $q ? (new VideoRepository($this->db()))->search($q) : [];
        Response::view('public/search', compact('q', 'results'));
    }

    public function ai(Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            Response::json(['error' => 'CSRF'], 422);
        }
        $query = trim((string)$request->input('query', ''));
        $service = new SearchService(
            new VideoRepository($this->db()),
            new GeminiClient((string)getenv('GEMINI_API_KEY'), (string)config('app')['gemini_model_id'], new Logger())
        );
        Response::json($service->ai($query));
    }
}
