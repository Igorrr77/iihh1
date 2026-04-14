# BotMother

Self-hosted modular monolith foundation for Telegram bot automation platform.

## Quick start
1. Configure DB in `config/database.php` or env vars.
2. Open `/install/index.php` in browser.
3. Open `/editor` for graph editor MVP.

## Entry points
- `index.php` — UI
- `api.php` — API endpoints
- `webhook.php` — Telegram webhook

## Smoke checks
- `php scripts/smoke_graph_validator.php` — validates graph guardrails (condition/http_request rules).
- `php scripts/smoke_graph_pipeline.php` — validates validate→compile pipeline and compiled entry/ports.
