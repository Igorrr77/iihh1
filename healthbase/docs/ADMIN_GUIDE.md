# ADMIN GUIDE

## Разделы
- Dashboard: счётчики видео, очередей, review.
- Видео: lock/manual reclassify.
- Manual review: спорные видео на проверку.

## Manual lock
Нажмите **Lock** на видео: AI/sync больше не перезаписывают ручные поля.

## Безопасность
- Вход: `password_hash/password_verify`
- CSRF во всех POST формах
- Admin routes защищены middleware
