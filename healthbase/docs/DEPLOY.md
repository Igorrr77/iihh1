# DEPLOY без терминала

1. Создайте сайт в панели (FastPanel/ISPmanager/cPanel).
2. Установите DocumentRoot на `healthbase/public`.
3. Загрузите файлы через файловый менеджер.
4. Создайте БД и пользователя в панели.
5. Пройдите веб-установщик.
6. Проверьте права записи для `storage/*`.
7. Отключите directory listing (`Options -Indexes` уже включен в `.htaccess`).
