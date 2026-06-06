<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Core\Database;
use App\Core\HttpClient;
use App\Core\TokenResolver;

abstract class AbstractConnector implements ConnectorInterface
{
    protected HttpClient $http;

    public function __construct()
    {
        $this->http = new HttpClient();
    }

    protected function request(string $method, string $url, array $headers = [], ?array $json = null): array
    {
        return $this->http->request($method, $url, $headers, $json);
    }

    protected function token(string $provider, string $fallbackEnvKey = ''): string
    {
        $resolver = new TokenResolver(Database::pdo());
        return $resolver->bearer($provider, $fallbackEnvKey);
    }

    protected function nowSql(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    protected function minPopularity(array $task, string $key): int
    {
        return (int) ($task['filters']['min_' . $key] ?? 0);
    }

    protected function passPopularity(array $task, array $popularity): bool
    {
        foreach (['likes', 'comments', 'views'] as $metric) {
            $min = $this->minPopularity($task, $metric);
            if (($popularity[$metric] ?? 0) < $min) {
                return false;
            }
        }

        return true;
    }

    protected function allowedType(array $task, string $type): bool
    {
        $allowed = $task['content_types'] ?? ['post'];
        return in_array($type, $allowed, true);
    }
}
