# WebConversion — план разработки до полного функционала

## 0) Цель

Довести текущий backend-MVP до полноценной production-платформы автовебинаров и вебинаров,
покрывающей весь целевой функционал: трансляции, сценарный движок, модерация, direct-response,
платежи, автоматизации, сквозная аналитика, AI-контур и кросс-девайс UI.

---

## 1) Принципы реализации

1. **Feature flags**: каждую крупную функцию включать по флагам.
2. **Backward compatibility API**: версии `/api/v1`, затем `/api/v2` без ломки клиентов.
3. **Observability-first**: метрики, логи, трассировка и алерты до масштабного релиза.
4. **Security-by-default**: RBAC, секреты, webhook signature, rate limits, audit trail.
5. **Incremental releases**: маленькие батчи, weekly release train.

---

## 2) Этапы (дорожная карта)

## Этап A — Production Foundation (2–3 недели)

### Объем
- RBAC и полноценная auth-модель: owner/admin/moderator/sales.
- Сессии/refresh-токены, ротация ключей, revoke.
- Конфиг окружений: local/stage/prod.
- Миграции по версиям (таблица `schema_migrations`).
- Docker + docker-compose для repeatable startup.
- Nginx + PHP-FPM production profile.
- CI/CD (lint, tests, build, deploy, rollback).

### Критерии готовности
- Релиз на stage и prod в один клик.
- Автоматический rollback при health-check fail.
- 0 критических уязвимостей по baseline security scan.

---

## Этап B — Streaming Core & Room Runtime (3–5 недель)

### Объем
- Унифицированный Video Provider Adapter (YouTube/Vimeo/Kinescope/Bunny).
- Room Runtime API: статусы комнаты, экраны ожидания/завершения.
- Гибкие режимы старта: instant / +1 min / fixed-time c timezone normalization.
- On-Demand и конвертация live -> auto с сохранением таймингов.
- Embeddable Webcast SDK (iframe + postMessage API + signed tokens).

### Критерии готовности
- Переключение провайдера без изменения внешнего API.
- Поддержка 3 режимов старта с правильным timezone.
- Встраивание комнаты на стороннем домене с безопасным токеном.

---

## Этап C — Сценарный движок и визуальный редактор (4–6 недель)

### Объем
- Event-sourcing timeline engine с типами событий (chat/offer/poll/redirect/ai-reply/data-slice).
- Макрокоманды и компиляция макросов в плоский таймлайн.
- Версии сценариев + diff + откат.
- UI-редактор: drag-drop timeline, секции, поиск, валидации.
- Импорт/экспорт JSON + адаптеры импорта из сторонних платформ.

### Критерии готовности
- Полный цикл: создать сценарий -> прогнать preview -> publish.
- Безошибочная загрузка/выгрузка JSON c проверкой схемы.
- Не менее 95% покрытие критических сценариев автотестами.

---

## Этап D — Chat, Moderation, AI Replies (3–4 недели)

### Объем
- Realtime chat (WebSocket/SSE) + fallback polling.
- Режимы: публичный/индивидуальный.
- Бан-листы, mute, keyword filters, антиспам эвристики.
- AI-автоответчик по prompt policy (что отвечать / что игнорировать).
- SLA-мониторинг latency для чата.

### Критерии готовности
- P95 задержка доставки сообщения < 1 сек.
- Индивидуальный режим изолирует участников полностью.
- AI-ответы логируются и доступны в moderation audit.

---

## Этап E — Direct Response и платежи (3–5 недель)

### Объем
- Конструктор offer-карточек (мини-лендинг блоки).
- Динамические таймеры и условия показа CTA.
- Checkout внутри комнаты + order statuses + retries.
- Интеграции: Stripe, PayPal, Braintree, Wayforpay.
- Post-webinar redirect policies.

### Критерии готовности
- Успешный end-to-end payment flow на всех подключенных PSP.
- Надежные webhook retries (idempotency keys, deduplication).
- Финансовый audit trail по каждому платежу.

---

## Этап F — Маркетинг автоматизации (4–6 недель)

### Объем
- Segment engine: visited/no-show/drop-before-offer/purchased и т.д.
- Email cadences + SMS + voice orchestration.
- Интеграции: топ-провайдеры email/SMS/voice.
- Мессенджеры (Telegram/WhatsApp/Viber/VK/Facebook Messenger) + CUID tracking.
- CRM routing: Salesforce/Hubspot/Pipedrive/Zoho/Dynamics/amoCRM/Bitrix24.

### Критерии готовности
- Триггеры отрабатывают без ручного вмешательства.
- Доставка событий в CRM с retry и dead-letter queue.
- Подтвержденная идемпотентность внешних интеграций.

---

## Этап G — Аналитика и AI ACE (4–6 недель)

### Объем
- Сквозная аналитика: UTM, CAC/ROI, funnels, retention heatmap.
- Data Slices в live/auto режимах.
- AI ACE pipeline: транскрипт -> summary/post/email/blog snippets.
- Отчеты и экспорт данных (CSV/JSON/API).

### Критерии готовности
- P95 time-to-insight < 5 минут после завершения вебинара.
- Воспроизводимые метрики для маркетинга и sales.
- Quality benchmark AI контента (ручной score + автоматический QA).

---

## Этап H — UI/UX финализация (3–4 недели)

### Объем
- Полный responsive UI для desktop/mobile/tablet/TV.
- Mobile-first комната: fixed video top + sticky CTA bottom.
- White-label: логотип, цвета, темы.
- Accessibility (контраст, клавиатурная навигация, aria).

### Критерии готовности
- Lighthouse mobile performance/UX в целевых пределах.
- Ключевые user journeys проходят без регрессий.

---

## 3) Параллельные потоки качества (идут во всех этапах)

- Автотесты: unit + integration + e2e + contract tests.
- Нагрузочное тестирование: room runtime, chat, webhook burst.
- Безопасность: SAST/DAST, pentest, secret scanning.
- Надежность: backups, restore drills, chaos experiments.

---

## 4) Релизная стратегия

1. Stage rollout на внутреннюю команду.
2. Private beta (5–10 клиентов).
3. Public beta с feature flags.
4. GA release с SLA и поддержкой.

---

## 5) Definition of Done для “полного функционала”

Продукт считается завершенным, когда:

- Закрыты этапы A–H.
- Все критичные сценарии покрыты автотестами и мониторингом.
- Пройдены нагрузка, безопасность и disaster recovery drills.
- Подтверждена стабильность на production в течение 30 дней без критических инцидентов.
