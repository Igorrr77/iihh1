# Commentor

Commentor — приложение на чистом PHP 8.2 (без фреймворков) для автоответов на комментарии в Instagram, Facebook, Facebook Pages и TikTok с генерацией через Gemini.

## Что реализовано

- Установка через `install.php`.
- Админ-панель: настройки промпта, CTA, подключение аккаунтов, аудит логов, мониторинг очереди и пошаговая диагностика подключения (Meta/TikTok).
- Webhook endpoint:
  - поддержка Meta payload (`object/entry/changes`) и упрощенного JSON;
  - верификация challenge;
  - проверка подписи `X-Hub-Signature-256` (Meta) или fallback `X-Webhook-Secret`.
- Идемпотентность: уникальность комментария `UNIQUE(account_id, external_comment_id)` + `INSERT OR IGNORE`.
- Очередь с retry/backoff:
  - статусы `pending/retry/in_progress/posted/dead/expired`;
  - экспоненциальный retry;
  - dead-letter статус (`dead`) после превышения max attempts.
- SLA-контроль: дедлайн ответа `RESPONSE_DEADLINE_SECONDS` (по умолчанию 180с).
- Токены в БД шифруются (`APP_ENCRYPTION_KEY`, sodium secretbox).
- Базовое автообновление access token через refresh token (при наличии OAuth metadata).
- Ответы: мягкий экспертный эмпатичный тон, только общие подходы, приглашение на консультацию.

## Быстрый запуск (Contabo + FastPanel)

1. Загрузите проект на VPS.
2. В FastPanel выставьте document root = `public`.
3. Откройте `https://your-domain/install.php`.
4. Заполните admin login, admin password, Gemini API key.
5. После установки заполните в `.env`:
   - `META_APP_SECRET` (для проверки подписи Meta).
6. Настройте cron каждую минуту:

```bash
* * * * * /usr/bin/curl -s "https://your-domain/cron.php?secret=CRON_SHARED_SECRET" >/dev/null 2>&1
```

## Прямой сценарий интеграции (Meta)

1. Создайте Meta App и подключите продукты: Webhooks + Instagram Graph API + Pages API.
2. Callback URL: `https://your-domain/webhook.php`.
3. Verify token = `WEBHOOK_VERIFY_TOKEN` из `.env`.
4. Подпишитесь на события комментариев.
5. Добавьте аккаунт в админке:
   - `platform`: instagram/facebook_page/facebook
   - `account_id`: внешний ID из `entry.id`
   - `access_token`: токен c правами reply.

## Упрощенный webhook формат

```json
{
  "platform": "instagram",
  "account_id": "178414...",
  "external_comment_id": "179876...",
  "external_media_id": "180123...",
  "commenter_handle": "username",
  "comment_text": "Что можно делать при хронической усталости?",
  "content_context": "Пост о восстановлении энергии"
}
```

Передавайте заголовок:

- `X-Webhook-Secret: <WEBHOOK_SHARED_SECRET>`

## TikTok

Для прямого вызова endpoint укажите в metadata JSON:

```json
{
  "reply_api_url": "https://open.tiktokapis.com/..."
}
```

`access_token` используется как Bearer.

## Безопасность

- Удалите или ограничьте `install.php` после установки.
- Используйте HTTPS.
- Не передавайте `CRON_SHARED_SECRET` и `WEBHOOK_SHARED_SECRET`.
- Включите signature check через `META_APP_SECRET`.


## Диагностика подключения в админке

В админ-панели есть кнопка «Запустить диагностику подключения».
Она по шагам проверяет:
- корректность токена и его расшифровку;
- Meta env-настройки (`WEBHOOK_VERIFY_TOKEN`, `META_APP_SECRET`);
- валидность Meta token через `/me`;
- совпадение `account_id` с доступом через Graph API;
- для TikTok — наличие `reply_api_url` и доступность endpoint.

По каждому шагу выводится статус `OK / FAIL / WARN` и текст «что исправить».
