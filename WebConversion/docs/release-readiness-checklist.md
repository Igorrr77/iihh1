# Release Readiness Checklist (GA)

Этот документ фиксирует разрыв между текущим baseline и критерием "полный релиз" (GA).

## Текущее состояние

- Есть backend foundation (маршруты, базовые сервисы, скрипты, unit-тесты).
- Есть план A–H с критериями готовности.
- По состоянию на текущий коммит закрыт только baseline-уровень, но не полный production scope.

## Что обязательно закрыть до GA

### A. Production Foundation
- Refresh/revoke/rotation токенов и полноценные сессии.
- Версионированные миграции через `schema_migrations`.
- Docker/Nginx/PHP-FPM production профиль.
- CI/CD pipeline с rollback и health-check gate.

### B. Streaming Core & Room Runtime
- Embeddable SDK (iframe + postMessage + signed token).
- Полный live -> auto conversion с сохранением таймингов.
- Реальные provider integrations (не только URL resolve).

### C. Scenario Engine
- Event-sourcing runtime для timeline.
- Visual timeline editor (preview/publish flow).
- Импорт/экспорт с валидацией схемы и миграцией версий.

### D. Chat, Moderation, AI
- Realtime transport (WebSocket/SSE).
- Антиспам, mute/ban, abuse controls уровня production.
- Метрики SLA (P95 < 1 сек) и наблюдаемость по чату.

### E. Direct Response + Payments
- Production checkout в комнате вебинара.
- Idempotency/dedup/retry политики webhook.
- E2E платежные сценарии по всем подключенным PSP.

### F. Marketing Automation
- Оркестрация email/SMS/voice с внешними провайдерами.
- CRM routing + retry + DLQ + idempotency.
- Трекинг идентификаторов для мессенджеров.

### G. Deep Analytics + ACE
- Сквозная аналитика (UTM/CAC/ROI/funnel attribution).
- Экспорты и отчеты production-уровня.
- QA/benchmark для AI-контента.

### H. UI/UX
- Полный responsive UI (desktop/mobile/tablet/TV).
- White-label и accessibility.
- Lighthouse/UX regression как release gate.

## Cross-cutting Quality Gates

- Integration/e2e/contract тесты по критичным сценариям.
- Load tests для room runtime/chat/webhook burst.
- Security: SAST/DAST/pentest/secret scanning.
- Reliability: backups + restore drills + DR подтверждение RTO/RPO.

## Явный критерий GA

GA можно считать достигнутым, только если:

1. Закрыты этапы A–H по acceptance criteria.
2. Пройдены security/load/DR проверки без критических блокеров.
3. Прод подтвержден минимум 30 днями стабильной работы без критических инцидентов.
