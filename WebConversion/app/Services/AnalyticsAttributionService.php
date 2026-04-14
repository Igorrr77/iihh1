<?php

declare(strict_types=1);

namespace App\Services;

final class AnalyticsAttributionService
{
    public function buildAttribution(array $rows): array
    {
        $report = [];
        foreach ($rows as $row) {
            $key = ($row['utm_source'] ?? '-') . '|' . ($row['utm_medium'] ?? '-') . '|' . ($row['utm_campaign'] ?? '-');
            if (!isset($report[$key])) {
                $report[$key] = [
                    'utm_source' => $row['utm_source'] ?? '-',
                    'utm_medium' => $row['utm_medium'] ?? '-',
                    'utm_campaign' => $row['utm_campaign'] ?? '-',
                    'leads' => 0,
                    'revenue_cents' => 0,
                    'spend_cents' => (int) ($row['spend_cents'] ?? 0),
                ];
            }
            $report[$key]['leads'] += (int) ($row['leads'] ?? 0);
            $report[$key]['revenue_cents'] += (int) ($row['revenue_cents'] ?? 0);
        }

        foreach ($report as &$item) {
            $spend = max(1, $item['spend_cents']);
            $item['roi'] = round(($item['revenue_cents'] - $item['spend_cents']) / $spend, 4);
            $item['cac_cents'] = $item['leads'] > 0 ? (int) floor($item['spend_cents'] / $item['leads']) : 0;
        }
        unset($item);

        return array_values($report);
    }

    public function timeToInsightMinutes(string $finishedAt, string $insightAt): int
    {
        $f = strtotime($finishedAt);
        $i = strtotime($insightAt);
        if ($f === false || $i === false || $i < $f) {
            return 0;
        }

        return (int) floor(($i - $f) / 60);
    }
}
