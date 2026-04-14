# Список задач до полного релиза (GA)

## 1) Platform & Security
- [ ] Закрыть production auth lifecycle: refresh rotation + revoke-all + session audit.
- [ ] Внедрить key rotation и хранение секретов через безопасный secret-store.
- [ ] Настроить обязательные security headers/CSP и централизованный audit trail.
- [ ] Провести SAST/DAST + внешний pentest и закрыть high/critical находки.

## 2) Infrastructure & Release Engineering
- [ ] Довести stage/prod окружения до one-click deploy + automatic rollback.
- [ ] Настроить CI/CD gates: lint + unit + integration + e2e + security + load smoke.
- [ ] Описать и проверить runbook для incident response и hotfix rollback.
- [ ] Подтвердить RTO/RPO на практических DR drills.

## 3) Streaming & Runtime
- [ ] Завершить provider-адаптеры с реальными SDK/API интеграциями.
- [ ] Поддержать режимы запуска (instant/+1/fixed timezone) с точной нормализацией TZ.
- [ ] Реализовать live->auto конвертацию с сохранением событийного тайминга.
- [ ] Добавить embeddable SDK (iframe + postMessage + signed token).

## 4) Scenario Engine
- [ ] Расширить event types и schema validation для timeline.
- [ ] Добавить версии/rollback/migration сценариев между форматами.
- [ ] Реализовать import/export adapters для сторонних платформ.
- [ ] Добавить preview/publish контрольный pipeline.

## 5) Chat, Moderation, AI
- [ ] Перевести realtime chat на production transport (SSE/WebSocket cluster-aware).
- [ ] Добавить anti-spam/anti-abuse эвристики и mute/ban lifecycle.
- [ ] Ввести SLA мониторинг (P95 latency, delivery errors, retry metrics).
- [ ] Добавить moderation audit по AI-ответам.

## 6) Payments & Direct Response
- [ ] Завершить checkout внутри комнаты и post-payment UX флоу.
- [ ] Закрыть idempotency/retry/dedup на всех webhook edge-cases.
- [ ] Полностью пройти E2E сценарии по всем PSP интеграциям.
- [ ] Финализировать финансовый audit trail и reconciliation отчёты.

## 7) Marketing Automation & CRM
- [ ] Довести сегментацию до production-триггеров (no-show, drop-off, purchased и т.д.).
- [ ] Интегрировать email/SMS/voice каналы с retry + DLQ.
- [ ] Реализовать messenger/CUID tracking с дедупликацией профилей.
- [ ] Подключить CRM routing (идемпотентность + повторная доставка).

## 8) Analytics & Reporting
- [ ] Доделать сквозную атрибуцию (UTM -> CAC/ROI -> revenue).
- [ ] Реализовать готовые отчеты и API/CSV экспорты для sales/marketing.
- [ ] Добавить quality benchmark для AI ACE контента.
- [ ] Настроить time-to-insight мониторинг и алерты.

## 9) QA Strategy
- [ ] Добавить integration/e2e/contract тесты для критических цепочек.
- [ ] Внедрить регулярные load/perf тесты (chat/runtime/webhook burst).
- [ ] Добавить тестовые фикстуры/seed данные для воспроизводимых прогонов.
- [ ] Настроить quality gates как обязательное условие деплоя.

## 10) Product Readiness (GA Gate)
- [ ] Закрыть этапы A–H по acceptance criteria.
- [ ] Подтвердить стабильность production 30 дней без критических инцидентов.
- [ ] Зафиксировать SLA и операционную поддержку (on-call, runbooks, dashboards).
- [ ] Провести финальный go/no-go review и выпустить GA.
