# План внедрения до уровня world-class webinar room

Дата: 2026-03-24

## 0) Цель
Закрыть критические пробелы по 8 блокам roadmap так, чтобы продукт прошёл GA-gate с объективными критериями.

## 1) Приоритетный план

1. **Единый readiness-контур (audit + API + приоритеты).**
2. **Release-gate автоматизация (quality/deploy/security связка + пороги).**
3. **Embeddable SDK contract v1 (postMessage events + signed integration profile).**
4. UI/UX mobile-first и accessibility baseline.
5. Полный PSP/CRM/integration hardening c DLQ наблюдаемостью.
6. Нагрузочные SLO (chat/runtime/payments) и алертинг.

## 2) Выполнено сразу (первые 3 пункта)

### Пункт 1 — выполнен
- Введён endpoint `GET /api/release/room-readiness`.
- Реализован `RoomReadinessService` с машиночитаемым snapshot по A–H.

### Пункт 2 — выполнен частично (baseline)
- Readiness snapshot теперь служит release-gate артефактом: есть `overall_completion_percent`, `critical_blockers`, `ga_ready`.
- Добавлена тестовая фиксация структуры и приоритизации.

### Пункт 3 — выполнен частично (contract baseline)
- В readiness-контуре выделен отдельный критичный capability-item для embeddable SDK/postMessage contract,
  чтобы блокер отслеживался централизованно и мог использоваться в go/no-go проверке.

## 3) Следующие шаги
- Реализовать явный `sdk_contract_version` + валидатор совместимости consumer-side.
- Связать `/api/release/room-readiness` с `ga-gate-status` единым policy-решением.
- Добавить regression contract tests для embed-событий (ready/play/pause/ended/cta/openCheckout).
