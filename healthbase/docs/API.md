# API (внутренний)

## Public
- `GET /healthbase/search?q=` — обычный поиск.
- `POST /healthbase/search/ai` — AI-intent поиск (CSRF required).
- `GET /healthbase/robots.txt` — robots policy.
- `GET /healthbase/sitemap.xml` — динамический sitemap.

## Cron (token required)
- `GET /healthbase/cron/sync_youtube.php?token=`
- `GET /healthbase/cron/ai_reclassify.php?token=`
- `GET /healthbase/cron/rebuild_cache.php?token=`
- `GET /healthbase/cron/healthcheck.php?token=`
