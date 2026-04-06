# INSTALL

## Требования
- PHP 8.2+
- MySQL 8+ / MariaDB
- Расширения: pdo_mysql, curl, json, mbstring

## Шаги
1. Откройте `https://hb.diabet.top/install/index.php`.
2. Пройдите шаг проверки окружения.
3. Введите параметры БД.
4. Введите Site URL (`https://hb.diabet.top`), YouTube/Gemini ключи, admin учетку.
5. Установщик создаст `.env`, таблицы и taxonomy, затем заблокируется (`storage/install.lock`).
