# Social Content Harvester (PHP 8.2, no frameworks)

Production-oriented админка и воркер для парсинга **публичного контента** из Facebook, Instagram, TikTok, Telegram, Reddit, Pinterest, Threads, Twitter/X.

## Что реализовано
- Установка одним файлом `install.php`.
- MySQL-схема (`sources`, `schedules`, `content_items`, `jobs`, `run_logs`, `oauth_states`, `oauth_accounts`).
- Авторизация админки (email + password hash).
- Полноценный OAuth слой:
  - consent + callback,
  - state/PKCE (где требуется),
  - token vault с multi-account хранением,
  - refresh lifecycle,
  - scope manager.
- Два режима задач: `topic` и `author`.
- Тонкие фильтры (`min_likes`, `min_comments`, `min_views`, типы `post/comment/video/reel`).
- Планировщик источников + очередь retry/backoff.
- CSV экспорт.
- Anti-ban базовый слой: proxy pool + UA rotation + jitter + рекомендации в гайде.
- CSRF защита POST-форм в админке и OAuth UI.
- Шифрование токенов в vault через AES-256-GCM (`APP_KEY`).

## UI
- `/public/login.php` — вход
- `/public/index.php` — админка
- `/public/oauth.php` — OAuth подключения
- `/public/guides.php` — подробный интерактивный гайд по подключению каждого провайдера и anti-ban практикам
- `/public/doctor.php` — диагностика работоспособности (DB, таблицы, OAuth-конфиг)
- `/public/export.php` — CSV
- `/public/worker.php` — worker+refresh lifecycle

## Cron
```bash
* * * * * /usr/bin/php /var/www/your-domain/public/worker.php >> /var/www/your-domain/storage/logs/cron.log 2>&1
```

## Важно
Для production убедитесь, что в кабинетах провайдеров настроены exact redirect URI и пройден app review для нужных scopes.


## Быстрая проверка
1. Войдите в админку.
2. Откройте `/public/doctor.php`.
3. Убедитесь, что все проверки имеют статус `OK`.


## Ограничения
Список известных неудобств и рисков: `docs/KNOWN_LIMITATIONS.md`.


## Руководства для новичков
- Развертывание без терминала: `docs/DEPLOYMENT_GUIDE_RU.md`
- Эксплуатация и подключение соцсетей: `docs/OPERATIONS_GUIDE_RU.md`
