# WebConversion

Backend-основа сервиса вебинаров/автовебинаров на **PHP 8.2 + MySQL**.

## Выполнены следующие 3 пункта плана (надежность + release readiness)

### 1) Backup/Restore baseline
- Добавлены скрипты:
  - `scripts/backup_db.php`
  - `scripts/restore_db.php`
- Бэкапы сохраняются в `storage/backups`.

### 2) Disaster drill baseline
- Добавлен `scripts/disaster_drill.php`:
  - проверяет наличие бэкапа,
  - показывает размер/хэш,
  - формирует команду восстановления.

### 3) Release strategy baseline (feature flags)
- Добавлены feature flags через БД:
  - таблица `feature_flags`,
  - `ReleaseController` API для list/set,
  - `FeatureFlagRepository`, `FeatureFlagService`.
- API:
  - `GET /api/release/flags`
  - `POST /api/release/flags`

## Команды

```bash
php scripts/backup_db.php
php scripts/disaster_drill.php
php scripts/restore_db.php /path/to/backup.sql
```

## Быстрый старт

```bash
php scripts/migrate.php
php scripts/create_admin.php admin@example.com strong_password owner
php -S 0.0.0.0:8080 -t public
```

## Тесты

```bash
php tests/run.php
find app public config scripts tests -name '*.php' -print0 | xargs -0 -n1 php -l
```
