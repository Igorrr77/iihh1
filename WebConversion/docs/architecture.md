# Архитектура WebConversion (план)

## 1) Streaming Core

- Хранение метаданных эфира в MySQL.
- Видео-трафик через внешние CDN/видеоплатформы (YouTube/Vimeo/Kinescope/Bunny).
- Единый Playback Adapter (PHP service) для переключения источника без изменения UI.
- Over-limit биллинг как отдельный модуль тарификации.

## 2) Сценарный движок

- Timeline Engine читает события из `webinar_events`.
- Поддержка событий: chat_message, offer_popup, redirect, poll, AI_answer, data_slice.
- Импорт/экспорт JSON + контроль версий сценариев.
- Макрокоманды компилируются в плоский список событий.

## 3) Chat & Moderation

- Режимы: публичный / индивидуальный.
- AI-ответчик применяет фильтры промпта (какие вопросы игнорировать).
- Антиспам: rate limit + blacklist + авто-модерация.

## 4) Marketing Automation

- Сегменты: registered_not_attended, left_before_offer, attended_offer_no_purchase, purchased.
- Email/SMS/Voice отправки через очередь задач.
- Интеграция CRM по webhooks/API push.

## 5) Analytics

- Событийный трекинг: joins/leaves, click offer, payment start/success.
- Heatmap retention по секундам.
- Data Slice: мгновенный слепок «кто онлайн» в критичный момент.

## 6) Безопасность и надежность

- JWT/Session auth для админов.
- Подпись webhook-запросов.
- Audit log админ-действий.
- Регулярные backup БД и сценариев.
