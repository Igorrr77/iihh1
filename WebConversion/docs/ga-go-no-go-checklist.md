# GA Go/No-Go Checklist

## Inputs required
- Статус этапов A–H (все completed).
- Отчет по critical incidents за последние 30 дней (должен быть 0).
- SLA registry (метрика, target, on-call owner, dashboard, runbook).
- Актуальный go/no-go review decision.

## Automation commands
1. `php scripts/ga_seed_acceptance.php` (для test/stage демонстрации).
2. `php scripts/ga_gate_check.php`.

## Definition
GA gate считается пройденным только если:
- `all_stages_closed = true`
- `critical_incidents_last_30d = 0`
- `sla_registered = true`
- `latest_go_no_go.decision = go`
- `ga_ready = true`
