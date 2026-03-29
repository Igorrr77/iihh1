<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class GaGateRepository
{
    public function setStage(string $code, string $name, bool $completed, ?string $owner, ?string $notes): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO release_stage_status (stage_code, stage_name, is_completed, completed_at, owner, notes)
             VALUES (:stage_code,:stage_name,:is_completed,:completed_at,:owner,:notes)
             ON DUPLICATE KEY UPDATE
                stage_name = VALUES(stage_name),
                is_completed = VALUES(is_completed),
                completed_at = VALUES(completed_at),
                owner = VALUES(owner),
                notes = VALUES(notes),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'stage_code' => strtoupper($code),
            'stage_name' => $name,
            'is_completed' => $completed ? 1 : 0,
            'completed_at' => $completed ? gmdate('Y-m-d H:i:s') : null,
            'owner' => $owner,
            'notes' => $notes,
        ]);
    }

    public function listStages(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT stage_code, stage_name, is_completed, completed_at, owner, notes, updated_at FROM release_stage_status ORDER BY stage_code ASC');
        return $stmt->fetchAll() ?: [];
    }

    public function upsertSla(string $metric, string $target, string $owner, string $dashboard, string $runbook): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO sla_registry (metric_key, target_value, owner_on_call, dashboard_url, runbook_url)
             VALUES (:metric_key,:target_value,:owner_on_call,:dashboard_url,:runbook_url)
             ON DUPLICATE KEY UPDATE
                target_value = VALUES(target_value),
                owner_on_call = VALUES(owner_on_call),
                dashboard_url = VALUES(dashboard_url),
                runbook_url = VALUES(runbook_url),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'metric_key' => $metric,
            'target_value' => $target,
            'owner_on_call' => $owner,
            'dashboard_url' => $dashboard,
            'runbook_url' => $runbook,
        ]);
    }

    public function listSla(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT metric_key, target_value, owner_on_call, dashboard_url, runbook_url, updated_at FROM sla_registry ORDER BY metric_key ASC');
        return $stmt->fetchAll() ?: [];
    }

    public function addIncident(string $severity, string $startedAt, ?string $resolvedAt, string $summary): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO production_incidents (severity, started_at, resolved_at, summary) VALUES (:severity,:started_at,:resolved_at,:summary)'
        );
        $stmt->execute([
            'severity' => $severity,
            'started_at' => $startedAt,
            'resolved_at' => $resolvedAt,
            'summary' => $summary,
        ]);
    }

    public function criticalIncidentsLastDays(int $days): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM production_incidents WHERE severity = "critical" AND started_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL :days DAY)');
        $stmt->bindValue(':days', max(1, $days), \PDO::PARAM_INT);
        $stmt->execute();
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function addGoNoGo(string $decision, string $reviewer, string $notes): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO go_no_go_reviews (review_date, decision, reviewer, notes) VALUES (UTC_TIMESTAMP(), :decision, :reviewer, :notes)');
        $stmt->execute([
            'decision' => $decision,
            'reviewer' => $reviewer,
            'notes' => $notes,
        ]);
    }

    public function latestGoNoGo(): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT review_date, decision, reviewer, notes FROM go_no_go_reviews ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function gaGateStatus(): array
    {
        $stages = $this->listStages();
        $stageMap = [];
        foreach ($stages as $stage) {
            $stageMap[(string) $stage['stage_code']] = (int) $stage['is_completed'] === 1;
        }

        $allStagesClosed = true;
        foreach (range('A', 'H') as $code) {
            if (($stageMap[$code] ?? false) !== true) {
                $allStagesClosed = false;
                break;
            }
        }

        $critical30d = $this->criticalIncidentsLastDays(30);
        $hasSla = count($this->listSla()) > 0;
        $latest = $this->latestGoNoGo();
        $goApproved = is_array($latest) && (($latest['decision'] ?? '') === 'go');

        return [
            'all_stages_closed' => $allStagesClosed,
            'critical_incidents_last_30d' => $critical30d,
            'sla_registered' => $hasSla,
            'latest_go_no_go' => $latest,
            'ga_ready' => $allStagesClosed && $critical30d === 0 && $hasSla && $goApproved,
        ];
    }
}
