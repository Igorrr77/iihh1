# Incident / Hotfix Rollback Runbook

## One-click deploy with rollback
1. Запустить `php scripts/deploy.php`.
2. Скрипт создаёт snapshot релиза, переключает `current` symlink.
3. Выполняет health-check.
4. При ошибке автоматически откатывает symlink на прошлый релиз.

## Incident process
1. Объявить инцидент и назначить Incident Commander.
2. Заморозить нерелевантные деплои.
3. Проверить дашборды ошибок/latency/availability.
4. При необходимости выполнить rollback через `current` symlink.
5. Зафиксировать таймлайн в postmortem.

## Hotfix flow
1. Отдельный hotfix branch.
2. Прогон CI gates (lint/unit/integration/security/load smoke).
3. Деплой через `scripts/deploy.php`.
4. Верификация health и ключевых endpoint.

## RTO/RPO evidence template
- Incident start:
- Degraded start:
- Restore complete:
- Data loss window (minutes):
- RTO met (yes/no):
- RPO met (yes/no):
