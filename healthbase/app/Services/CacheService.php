<?php

declare(strict_types=1);

namespace App\Services;

class CacheService
{
    public function remember(string $key, int $ttl, callable $resolver): string
    {
        $path = $this->path($key);
        if (is_file($path)) {
            $payload = json_decode((string)file_get_contents($path), true);
            if (($payload['expires_at'] ?? 0) > time()) {
                return (string)($payload['content'] ?? '');
            }
        }

        $content = (string)$resolver();
        file_put_contents($path, json_encode([
            'expires_at' => time() + $ttl,
            'content' => $content,
        ], JSON_UNESCAPED_UNICODE));

        return $content;
    }

    public function clear(): void
    {
        foreach (glob(root_path('storage/cache/*.json')) ?: [] as $file) {
            @unlink($file);
        }
    }

    private function path(string $key): string
    {
        return root_path('storage/cache/' . md5($key) . '.json');
    }
}
