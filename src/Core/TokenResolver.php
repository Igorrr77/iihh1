<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class TokenResolver
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function bearer(string $provider, string $fallbackEnvKey = ''): string
    {
        $repo = new OAuthAccountRepository($this->pdo);
        $row = $repo->findActive($provider);
        if ($row && !empty($row['access_token'])) {
            return (string) $row['access_token'];
        }

        if ($fallbackEnvKey !== '') {
            return (string) Env::get($fallbackEnvKey, '');
        }

        return '';
    }
}
