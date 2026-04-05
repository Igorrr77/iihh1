<?php

declare(strict_types=1);

namespace App\Services;

use App\AI\GeminiClient;
use App\Repositories\VideoRepository;

class SearchService
{
    public function __construct(private VideoRepository $videos, private GeminiClient $gemini)
    {
    }

    public function quick(string $query): array
    {
        return $this->videos->search($query);
    }

    public function ai(string $query): array
    {
        $prompt = file_get_contents(root_path('app/AI/Prompts/search_intent_v1.txt'));
        $json = $this->gemini->generateJson(str_replace('{{query}}', $query, $prompt));
        $normalized = $json['must_have'][0] ?? $query;
        $videos = $this->videos->search($normalized);

        return ['intent' => $json, 'videos' => $videos];
    }
}
