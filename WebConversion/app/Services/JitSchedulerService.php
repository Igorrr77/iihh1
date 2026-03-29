<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

final class JitSchedulerService
{
    public function calculateStartAt(string $mode, string $timezone, ?string $fixedStartAtUtc = null, ?string $fixedStartLocal = null): string
    {
        $tz = $this->safeTimezone($timezone);
        $nowUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return match ($mode) {
            'instant' => $nowUtc->format('Y-m-d H:i:s'),
            'plus_1_min' => $nowUtc->modify('+1 minute')->format('Y-m-d H:i:s'),
            'fixed' => $this->normalizeFixed($tz, $fixedStartAtUtc, $fixedStartLocal, $nowUtc),
            default => $nowUtc->format('Y-m-d H:i:s'),
        };
    }

    private function normalizeFixed(DateTimeZone $tz, ?string $fixedStartAtUtc, ?string $fixedStartLocal, DateTimeImmutable $fallbackUtc): string
    {
        if (is_string($fixedStartAtUtc) && $fixedStartAtUtc !== '') {
            return (new DateTimeImmutable($fixedStartAtUtc, new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        }

        if (is_string($fixedStartLocal) && $fixedStartLocal !== '') {
            $local = new DateTimeImmutable($fixedStartLocal, $tz);
            return $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }

        return $fallbackUtc->modify('+5 minute')->format('Y-m-d H:i:s');
    }

    private function safeTimezone(string $timezone): DateTimeZone
    {
        try {
            return new DateTimeZone($timezone);
        } catch (\Throwable) {
            return new DateTimeZone('UTC');
        }
    }
}
