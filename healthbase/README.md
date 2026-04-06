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

## ВАЖНО по запуску
Проект теперь запускается **из корня сайта**, без переключения DocumentRoot на `public`.

## Быстрый старт (для hb.diabet.top)
1. Загрузите содержимое папки `healthbase` в корень сайта `hb.diabet.top`.
2. Откройте `https://hb.diabet.top/install/index.php`.
3. Пройдите мастер установки.
4. Войдите в админку: `https://hb.diabet.top/admin/login.php`.

Перед запуском убедитесь, что папки `storage/cache`, `storage/logs`, `storage/temp`, `storage/exports` доступны на запись.

Подробности: `docs/INSTALL.md`, `docs/DEPLOY.md`, `docs/CRON.md`.
