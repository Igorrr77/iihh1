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

## Быстрый старт
1. Загрузите папку `healthbase` на хостинг.
2. Откройте `https://your-domain/install/index.php`.
3. Пройдите мастер установки.
4. Войдите в админку: `/admin/login.php`.

Подробности: `docs/INSTALL.md`, `docs/DEPLOY.md`, `docs/CRON.md`.
