# Sales Speech Intelligence (Telegram + Admin Panel)

MVP-приложение на **PHP 8.2 + MySQL + HTML/CSS/JS** для multi-tenant анализа клиентской речи в Telegram с live-подсказками продавцу.

## Что реализовано

- Multi-tenant модель: несколько чатботов (тенантов) в одном приложении.
- Telegram webhook обработка входящих сообщений клиента.
- Анализ клиентских реплик порциями текста через Gemini (`gemini-3.1-flash-lite`) с сохранением:
  - тональности/эмоций,
  - уверенности,
  - возражений,
  - pain points,
  - lead score,
  - churn risk,
  - профиля личности + рычагов влияния,
  - live coaching + речевых паттернов.
- Автоответ клиенту от имени продавца на базе паттернов.
- Веб-админка с авторизацией:
  - live-лента подсказок,
  - загрузка контекста продукта,
  - настройки интеграций CRM/webhook,
  - экспорт JSON/CSV,
  - удаление данных по запросу.
- Хранение транскриптов и аналитики до 1 месяца + cleanup-скрипт.

## Быстрый старт

1. Скопируйте конфиг:
   ```bash
   cp config/config.example.php config/config.php
   ```
2. Создайте БД и примените схему:
   ```bash
   mysql -u root -p sales_ai < schema.sql
   ```
3. Создайте tenant (пример):
   ```sql
   INSERT INTO tenants (name, slug, telegram_bot_token) VALUES ('Main Tenant', 'main-tenant', '123:telegram-bot-token');
   ```
4. Создайте admin-пользователя:
   ```bash
   php scripts/create_admin.php 1 admin@example.com StrongPassword123
   ```
5. Установите Telegram webhook:
   ```bash
   php scripts/set_webhook.php main-tenant
   ```
6. Запустите PHP:
   ```bash
   php -S 0.0.0.0:8080 -t public
   ```

## Структура

- `public/index.php` — маршрутизация API + webhook + admin page.
- `public/views/admin.php` — UI админпанели.
- `public/assets/*` — стили и JS (цвета в стиле Facebook).
- `src/*` — бизнес-логика (Auth, Telegram, Analysis, Gemini client).
- `schema.sql` — MySQL схема.
- `scripts/cleanup.php` — cron-cleanup данных старше 30 дней.

## Cron для Contabo VPS

```cron
0 2 * * * /usr/bin/php /path/to/app/scripts/cleanup.php >> /var/log/sales_ai_cleanup.log 2>&1
```

## Ограничения текущего MVP

- Реалтайм реализован на основе текстовых chunks из Telegram сообщений (пуллинг ленты каждые 4 сек).
- Для голосовых сообщений/звонков Telegram нужен отдельный ASR pipeline (получение media file + транскрипция) — можно добавить следующей итерацией.
- Для production рекомендуется вынести очереди/worker (RabbitMQ/Redis) и websocket-сервер для мгновенного push.
