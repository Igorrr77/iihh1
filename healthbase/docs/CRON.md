# HTTP CRON

Используйте токен из `.env` (`CRON_TOKEN`).

- `/cron/sync_youtube.php?token=...`
- `/cron/ai_reclassify.php?token=...`
- `/cron/rebuild_cache.php?token=...`
- `/cron/healthcheck.php?token=...`

## Рекомендуемая частота
- sync: каждые 30 мин
- ai_reclassify: каждые 15 мин
- rebuild_cache: 1 раз в час или по событию
- healthcheck: каждые 15–30 мин

Если HTTP cron не задан, dashboard в админке триггерит pseudo-cron после истечения интервала.
