<?php

declare(strict_types=1);

namespace App\Services;

final class FeatureFlagService
{
    public function normalizeKey(string $key): string
    {
        return strtolower(trim($key));
    }
}
