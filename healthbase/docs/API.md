# API (внутренний)

## Public
- `GET /search?q=` — обычный поиск.
- `POST /search/ai` — AI-intent поиск (CSRF required).
- `GET /robots.txt` — robots policy.
- `GET /sitemap.xml` — динамический sitemap.

## Cron (token required)
- `GET /cron/sync_youtube.php?token=`
- `GET /cron/ai_reclassify.php?token=`
- `GET /cron/rebuild_cache.php?token=`
- `GET /cron/healthcheck.php?token=`
