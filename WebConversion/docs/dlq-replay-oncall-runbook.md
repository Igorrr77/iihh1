# DLQ Replay & On-Call Handoff Runbook

Дата: 2026-03-25

## Цель
Описать оперативный процесс для ручного replay задач в DLQ (каналы + CRM) и передачу ответственности on-call инженеру.

## 1) Проверка состояния DLQ
1. Запросить сводку:
   - `GET /api/marketing/dlq-summary`
2. Определить очередь с наибольшим `dlq_count`.
3. Проверить последние ошибки (`last_error_code`, `last_error_reason`) в БД.

## 2) Каналы (email/sms/voice) — replay
1. Проверить причину DLQ (например `provider_rejected`, `delivery_timeout`).
2. Исправить payload/template/provider mapping.
3. Перевести запись из `dlq` в `failed` и выставить `next_retry_at=UTC_TIMESTAMP()`.
4. Выполнить:
   - `POST /api/marketing/process-channel-queue?channel=<channel>`
5. Повторно проверить `GET /api/marketing/dlq-summary`.

## 3) CRM — replay
1. Проверить провайдера и payload.
2. Исправить источник ошибки (credentials/transport/data mapping).
3. Перевести запись из `dlq` в `failed` + `next_retry_at=UTC_TIMESTAMP()`.
4. Выполнить:
   - `POST /api/marketing/process-crm-queue?provider=<provider>`
5. Подтвердить снижение DLQ счетчика.

## 4) Коммуникация и handoff
Перед передачей смены зафиксировать:
- Какой провайдер/канал затронут.
- Сколько сообщений в DLQ до/после replay.
- Какие записи остаются блокирующими.
- ETA следующей проверки.

Минимальный handoff-шаблон:
- Incident ID:
- Affected queue/provider:
- Root cause:
- Actions performed:
- Current status:
- Next action + owner + ETA:

## 5) Критерий закрытия инцидента
- `dlq-summary` показывает 0 для затронутой очереди/провайдера,
- повторная обработка проходит без новых DLQ,
- запись handoff/постмортема создана.
