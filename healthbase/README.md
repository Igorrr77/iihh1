# База знаний Международного Института Здоровья Человека

Production-oriented веб-приложение на **PHP 8.2 + MySQL + HTML/CSS/JS** без фреймворков.

## Что умеет v1
- Веб-установщик через браузер.
- YouTube sync через uploads playlist (без `search.list` как основы).
- Импорт видео, фильтр длинных роликов (`>300s`).
- AI-классификация (rules -> Gemini classification -> Gemini validation -> confidence -> auto publish/manual review).
- Очередь ручной проверки и manual lock.
- Публичные страницы: главная, темы, видео, поиск, видео-деталь, старт.
- Админка: dashboard, видео, manual review.
- HTTP cron endpoints + pseudo-cron fallback.

## Режим запуска
Поддерживается запуск из поддиректории, например `https://diabet.top/healthbase` (без переноса в `/public`).

## Быстрый старт (для diabet.top/healthbase)
1. Загрузите содержимое папки `healthbase` в каталог `diabet.top/healthbase`.
2. Откройте `https://diabet.top/healthbase/install/index.php`.
3. На шаге 3 установщика укажите `Base path: /healthbase`.
4. Войдите в админку: `https://diabet.top/healthbase/admin/login.php`.

Перед запуском убедитесь, что папки `storage/cache`, `storage/logs`, `storage/temp`, `storage/exports` доступны на запись.
