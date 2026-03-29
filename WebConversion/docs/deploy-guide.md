# Deploy Guide (Pre-Deploy Verification + Release)

## 1) Pre-deploy checks (обязательно)

```bash
cd WebConversion
php tests/run.php
find app public config scripts tests -name '*.php' -print0 | xargs -0 -n1 php -l
php scripts/security_scan.php
```

### Runtime smoke/e2e/contract/load (локально)

```bash
nohup php -S 127.0.0.1:8080 -t public >/tmp/wc_server.log 2>&1 &
echo $! >/tmp/wc_server.pid

php scripts/integration_smoke.php http://127.0.0.1:8080
php scripts/contract_test.php http://127.0.0.1:8080
php scripts/e2e_test.php http://127.0.0.1:8080
php scripts/load_test.php http://127.0.0.1:8080 20

kill $(cat /tmp/wc_server.pid)
rm -f /tmp/wc_server.pid
```

## 2) DB and fixtures

```bash
php scripts/migrate.php
php scripts/seed_fixtures.php   # optional for stage checks
```

## 3) GA gate verification (release governance)

```bash
# optional for stage/demo env
php scripts/ga_seed_acceptance.php

# must return ga_ready=true and exit code 0
php scripts/ga_gate_check.php
```

## 4) Deploy

```bash
php scripts/deploy.php
```

Deploy script creates a release snapshot, switches `current` symlink and rolls back automatically if health-check fails.

> Важно: health-check URL берётся из `APP_URL` в `.env`.
> Для вашего кейса: `APP_URL=http://1968.us/WebConversion`.
> Скрипт проверит `http://1968.us/WebConversion/health`.

## 5) Post-deploy verification

- `GET /health` => 200
- `GET /api/release/ga-gate-status` (admin auth)
- Check dashboards/on-call alerts for 15-30 minutes after deployment.


## 6) Browser-only mode (script runner)

Если вы запускаете всё из браузера, используйте:

`/script-runner.php?key=<SCRIPT_RUNNER_KEY>&task=<task_name>`

Доступные задачи:
- `migrate`, `tests`, `quality_gate`, `security_scan`, `backup_db`, `deploy`,
- `disaster_drill`, `ga_gate_check`, `go_no_go_report`, `ga_stabilization_report`,
- `provider_matrix_check`, `production_stability_check`,
- `create_admin` (параметры: `email`, `password`, `role`).

Примеры:
- `https://1968.us/WebConversion/script-runner.php?key=YOUR_KEY&task=migrate`
- `https://1968.us/WebConversion/script-runner.php?key=YOUR_KEY&task=quality_gate`
- `https://1968.us/WebConversion/script-runner.php?key=YOUR_KEY&task=deploy`
- `https://1968.us/WebConversion/script-runner.php?key=YOUR_KEY&task=create_admin&email=admin@example.com&password=StrongPass123&role=owner`

> Без `SCRIPT_RUNNER_KEY` (или `ADMIN_API_KEY` fallback) запуск запрещён.
