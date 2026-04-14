<?php

declare(strict_types=1);

namespace App\Services;

final class OfferService
{
    public function expiresAt(string $activatedAtUtc, int $ttlSec): string
    {
        $dt = new \DateTimeImmutable($activatedAtUtc, new \DateTimeZone('UTC'));
        return $dt->modify('+' . $ttlSec . ' seconds')->format('Y-m-d H:i:s');
    }
}
