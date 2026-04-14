# План на 3 спринта и выполненная реализация

## Sprint 1 — Foundation hardening

### План
- Ввести версионированные миграции (`schema_migrations`) и последовательный запуск `*.sql`.
- Усилить auth lifecycle: access + refresh токены, refresh-rotation, logout/revoke.
- Добавить SQL-миграцию для refresh token storage.

### Реализовано
- `scripts/migrate.php` теперь применяет все миграции по версии файла и записывает результат в `schema_migrations`.
- Добавлены `auth_refresh_tokens` и поля revoke/rotation для `api_tokens`.
- Реализованы API: `POST /api/auth/refresh`, `POST /api/auth/logout`.
- `POST /api/auth/login` возвращает пару `access_token` + `refresh_token`.

## Sprint 2 — Chat realtime baseline

### План
- Добавить режим realtime для чата (SSE baseline) и cursor-based polling.
- Не ломать существующий polling endpoint.

### Реализовано
- Добавлен endpoint `GET /api/chat/stream` (SSE event `chat`).
- `POST /api/chat/list` и репозиторий чата теперь поддерживают `since_id` для инкрементальной догрузки.
- Сохранена обратная совместимость текущих чат-операций.

## Sprint 3 — Payments idempotency + analytics export

### План
- Добавить idempotency/dedup для webhook-платежей.
- Добавить экспорт аналитики в CSV для отчётности.

### Реализовано
- Добавлена таблица `payment_webhook_events` с уникальностью `(provider, provider_event_id)`.
- Webhook теперь требует `event_id` в payload, делает dedup и не повторяет изменение статуса при дубле.
- Добавлен endpoint `GET /api/analytics/export-csv`.

## Осталось после 3 спринтов

- Полноценные provider SDK-интеграции и боевые PSP edge-cases.
- Realtime chat SLA и масштабирование (multi-node, persistent channels).
- E2E/integration/contract tests в полном покрытии критических цепочек.
- UI/UX и white-label/accessibility этапы, которые пока вне backend scope.
