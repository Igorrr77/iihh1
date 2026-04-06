# DEPLOY без терминала (diabet.top/healthbase, без /public)

## Сценарий
Вы размещаете проект в папке:

`/var/www/diabet_top_usr/data/www/diabet.top/healthbase`

и открываете сайт как:

`https://diabet.top/healthbase`

## Пошагово
1. В панели оставьте корень основного сайта `diabet.top` как есть.
2. Загрузите **содержимое** проекта `healthbase` в папку `diabet.top/healthbase`.
3. Проверьте, что в `diabet.top/healthbase` есть: `index.php`, `.htaccess`, `app/`, `assets/`, `install/`, `cron/`, `storage/`.
4. Создайте БД и пользователя MySQL.
5. Откройте `https://diabet.top/healthbase/install/index.php`.
6. На шаге 3 установщика укажите:
   - `Site URL`: `https://diabet.top/healthbase`
   - `Base path`: `/healthbase`
7. Дайте права записи на `storage/cache`, `storage/logs`, `storage/temp`, `storage/exports`.

## Где находится storage
`/var/www/diabet_top_usr/data/www/diabet.top/healthbase/storage`

## Почему это работает
- Приложение поддерживает `APP_BASE_PATH=/healthbase`.
- Все ссылки/редиректы/роутинг учитывают base path.
- Не требуется перенос в `/public`.
