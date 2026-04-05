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
