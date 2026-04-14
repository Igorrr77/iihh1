# План внедрения функционала, который ещё не реализован

Дата: 2026-03-24

## 1) Что ещё не внедрено (критичные зоны)

### A. Product/UI (самый большой пробел)
- Полноценная UI-комната (desktop/mobile/tablet) с production UX.
- White-label кабинет (темы, бренд-пакеты, кастомные домены, ассеты).
- Accessibility baseline (WCAG-контраст, клавиатура, ARIA, фокус-менеджмент).

### B. Embeddable SDK и фронтовой контракт
- Формальная версия JS SDK (`sdk_contract_version`) и обратная совместимость.
- Полный postMessage event-contract: `ready`, `play`, `pause`, `ended`, `cta_click`, `checkout_open`.
- Документация интеграции + sandbox-примеры для партнёрских сайтов.

### C. GA-операционка и reliability
- Автоматический rollback policy в CI/CD при деградации health-check.
- Реальные SLO/SLA дашборды с алертингом по chat/runtime/payments.
- Подтверждённый 30-дневный период без критических инцидентов.

### D. Интеграции и enterprise-hardening
- Полный e2e-матрицинг PSP (Stripe/PayPal/Braintree/Wayforpay) с edge-case сценариями.
- CRM/DLQ observability: повторные попытки, дедупликация, причины отказов.
- Канальные delivery-коннекторы (messengers/SMS/voice) уровня production.

---

## 2) План внедрения (6 спринтов)

## Спринт 1 — UI foundation + SDK contract draft
**Цель:** заложить UI-каркас комнаты и формальный SDK-контракт.

- Создать frontend-room shell: видео-зона, чат, CTA-слой, статусы waiting/live/ended.
- Определить `sdk_contract_version=v1`, JSON schema событий и ошибок.
- Добавить contract-tests для postMessage-протокола.

**Результат спринта:** есть запускаемая комната и верифицируемый контракт SDK v1.

## Спринт 2 — Mobile-first + Accessibility
**Цель:** довести ключевые пользовательские пути до production-уровня.

- Mobile UX: fixed video top, sticky CTA bottom, safe area support.
- A11y baseline: aria-labels, tab-order, keyboard shortcuts, contrast-темы.
- Lighthouse/perf budget для комнаты.

**Результат спринта:** room UX работает на mobile и проходит базовые a11y/perf-gates.

## Спринт 3 — Release automation и SLO gates
**Цель:** автоматизировать релизные решения по объективным метрикам.

- Связать `ga-gate-status` + `room-readiness` в единый policy endpoint.
- Добавить rollback-триггеры при нарушении SLO (chat latency, payment errors).
- Автоматизировать go/no-go отчёт в CI.

**Результат спринта:** релиз блокируется автоматически при провале quality/SLO.

## Спринт 4 — PSP/checkout hardening
**Цель:** закрыть платежные edge-cases и финансовую наблюдаемость.

- Расширить e2e-тест матрицу по всем PSP.
- Усилить retries + idempotency + reconciliation отчёты.
- Добавить операционный dashboard для отказов checkout.

**Результат спринта:** платежный контур стабилен и прозрачен по метрикам.

## Спринт 5 — CRM/каналы коммуникаций
**Цель:** production-надежность маркетинг-цепочек.

- Реализовать DLQ visibility и retry policy c reason-кодами.
- Укрепить интеграции CRM/messenger/SMS/voice.
- Добавить интеграционные контракты и нагрузочные проверки очередей.

**Результат спринта:** события reliably доходят до CRM и каналов коммуникации.

## Спринт 6 — GA stabilization
**Цель:** пройти финальный GA-gate.

- 30-дневный run с нулём critical incidents.
- Финальный security sweep (SAST/DAST + секреты + ручной checklist).
- Финальный go/no-go review и публикация SLA-паспорта.

**Результат спринта:** готовность к GA подтверждена формальными критериями.

---


## Статус выполнения Sprint 1 (реализовано в коде)
- Добавлен UI room shell: `public/room-shell.html` (видео зона, чат-панель, sticky CTA, mobile layout).
- Добавлен SDK contract API: `GET /api/stream/sdk-contract` с `sdk_contract_version=v1` и event map.
- Добавлен unit-test `tests/unit/EmbedSdkContractServiceTest.php` и контрактная проверка `scripts/contract_test.php` на endpoint SDK.

---

## Статус выполнения Sprint 2 (реализовано в коде)
- Улучшен `public/room-shell.html`: mobile-first layout + safe-area + sticky CTA для мобильных.
- Добавлены accessibility улучшения: skip-link, aria-label, focus-visible, keyboard shortcuts, high-contrast режим.
- Добавлен perf budget check `scripts/perf_budget_check.php`, подключён в `scripts/quality_gate.php`.

---

## Статус выполнения Sprint 3 (реализовано в коде)
- Добавлен unified policy gate endpoint: `GET/POST /api/release/policy-gate`, который объединяет `ga-gate-status` + `room-readiness` + SLO checks.
- Реализованы rollback-триггеры по SLO метрикам (`chat_latency_p95_ms`, `payment_error_rate`, `runtime_error_rate`).
- Добавлен скрипт CI-отчёта `scripts/go_no_go_report.php` и шаг GitHub Actions `Go/No-Go advisory report`.

---

## Статус выполнения Sprint 4 (реализовано в коде)
- Усилен payments-контур: нормализация провайдеров/валюты, валидация суммы checkout, retry policy.
- Добавлены payment retry операции: `POST /api/payments/retry-checkout` с лимитом retry attempts.
- Добавлен ops dashboard endpoint: `GET /api/payments/ops-dashboard` (reconciliation + provider breakdown + retry queue).
- Добавлена миграция `008_payment_retry_hardening.sql` для retry-полей и индекса очереди.

---

## Статус выполнения Sprint 5 (реализовано в коде)
- Усилены маркетинг-очереди: retry policy по каналам, reason-codes и управляемый перевод в DLQ.
- Добавлена CRM retry/dlq обработка: `POST /api/marketing/process-crm-queue`.
- Добавлен DLQ visibility endpoint: `GET /api/marketing/dlq-summary`.
- Добавлена миграция `009_marketing_reliability_dlq.sql` для полей ошибок и retry индексов.

---

## Статус выполнения Sprint 6 (реализовано в коде)
- Добавлен GA passport endpoint: `GET /api/release/ga-passport`.
- Добавлен сервис `GaStabilizationService` с финальной сборкой checks: GA gate + room readiness + policy gate + security posture.
- Добавлен скрипт `scripts/ga_stabilization_report.php` (advisory mode) и CI шаг `GA stabilization advisory report`.

---

## Дополнительно после Sprint 6 (реализовано)
- Добавлен `scripts/provider_matrix_check.php` для контроля полноты PSP e2e matrix.
- Добавлен `scripts/production_stability_check.php` для проверки 30-day stability окна по критическим инцидентам.
- Добавлен runbook `docs/dlq-replay-oncall-runbook.md` для DLQ replay и on-call handoff.

---

## 3) Приоритизация (что делать прямо сейчас)

1. **Собрать фактические production-данные за 30 дней (это организационный шаг, не код).**
2. **Провести финальный go/no-go комитет и зафиксировать решение в release-артефактах.**
3. **Провести dry-run аварийного восстановления на stage перед production cutover.**

---

## 4) Definition of Done для “функционал внедрён”

Функционал считаем внедрённым, если одновременно выполнено:
- Есть production UI-комната (responsive + a11y + perf budgets).
- Есть стабильный и версионированный embeddable SDK contract.
- Релизные решения автоматизированы quality/SLO-gates.
- Полный e2e контур payments/CRM/channels проходит без критичных дефектов.
- Подтверждён 30-дневный стабильный production-run.
