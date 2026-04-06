# TROUBLESHOOTING

## 403 на cron
- Проверьте `CRON_TOKEN` в URL.
- Убедитесь, что `.env` читается приложением.

## YouTube sync не импортирует
- Проверьте `YOUTUBE_CHANNEL_ID`, `YOUTUBE_API_KEY`.
- Проверьте квоты YouTube API.
- Смотрите `storage/logs/sync.log`.

## AI jobs не обрабатываются
- Проверьте `GEMINI_API_KEY` и модель.
- Запустите `/cron/ai_reclassify.php?token=...`.
- Смотрите `storage/logs/ai.log`.

## При входе в админку снова открывается установщик
- Проверьте наличие файлов `healthbase/.env` и `healthbase/storage/install.lock`.
- Если одного из файлов нет — установка была завершена не полностью из-за прав записи.
- Дайте права записи на корень проекта (для `.env`) и `storage/` (для `install.lock`), затем повторите установку.
