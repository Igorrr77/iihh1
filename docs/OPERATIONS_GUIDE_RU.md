# Эксплуатация системы для новичков: как работать каждый день

## 1) Основная логика
- Вы создаете **источники** (по теме или по автору).
- Планировщик создаёт задачи.
- Worker (cron) забирает задачи и сохраняет контент.
- Результат смотрите в админке и выгружаете в CSV.

---

## 2) Вход и главные страницы
- Вход: `/public/login.php`
- Главная админка: `/public/index.php`
- OAuth подключения: `/public/oauth.php`
- Гайд по соцсетям: `/public/guides.php`
- Проверка системы: `/public/doctor.php`
- CSV экспорт: `/public/export.php`

---

## 3) Подключение соцсетей (детально)

## Общий принцип для всех
1. Создайте приложение в кабинете разработчика соцсети.
2. Добавьте Redirect URI из вашей системы:
   - формата: `https://ваш-домен/public/oauth.php?provider=...&action=callback`
3. Получите `Client ID` и `Client Secret`.
4. Запишите их в `.env` (через `install.php` или вручную через файловый менеджер).
5. В админке откройте `/public/oauth.php`, нажмите **Подключить**.
6. Пройдите подтверждение прав (consent).

### Facebook
- Провайдер: `facebook`
- Redirect URI:  
  `https://ваш-домен/public/oauth.php?provider=facebook&action=callback`
- Минимум scopes: `pages_read_engagement,pages_read_user_content`

### Instagram
- Провайдер: `instagram`
- Redirect URI:  
  `https://ваш-домен/public/oauth.php?provider=instagram&action=callback`
- Минимум scopes: `instagram_basic,pages_show_list`

### Threads
- Провайдер: `threads`
- Redirect URI:  
  `https://ваш-домен/public/oauth.php?provider=threads&action=callback`
- Минимум scopes: `threads_basic`

### Twitter / X
- Провайдер: `x`
- Redirect URI:  
  `https://ваш-домен/public/oauth.php?provider=x&action=callback`
- Минимум scopes: `tweet.read users.read offline.access`

### TikTok
- Провайдер: `tiktok`
- Redirect URI:  
  `https://ваш-домен/public/oauth.php?provider=tiktok&action=callback`
- Минимум scopes: read-only (по вашему кабинету TikTok)

### Pinterest
- Провайдер: `pinterest`
- Redirect URI:  
  `https://ваш-домен/public/oauth.php?provider=pinterest&action=callback`
- Минимум scopes: `pins:read,boards:read,user_accounts:read`

### Reddit
- Провайдер: `reddit`
- Redirect URI:  
  `https://ваш-домен/public/oauth.php?provider=reddit&action=callback`
- Минимум scopes: `identity read history`

### Telegram
- Используется токен бота (`TELEGRAM_BOT_TOKEN`) в `.env`.
- OAuth не нужен.

---

## 4) Создание парсинга
1. Откройте `/public/index.php`.
2. В блоке «Создать источник + планировщик»:
   - Платформа
   - Режим:
     - **По теме** (`topic`) — заполните ключевые слова
     - **По автору** (`author`) — заполните handle
   - Порог популярности: лайки/комментарии/просмотры
   - Тип контента: post/comment/video/reel
   - Интервал запуска (минуты)
3. Нажмите «Создать и запустить».

---

## 5) Как понимать, что всё работает
1. На главной странице растут записи в блоке «Очередь».
2. Появляется контент в блоке «Контент».
3. На `/public/doctor.php` всё в OK.
4. В CSV выгрузке есть новые строки.

---

## 6) Anti-ban рекомендации (очень важно)
1. Используйте только официальные API и разрешенные scopes.
2. Не ставьте слишком частый интервал (обычно 5–15+ минут).
3. Настройте ротацию User-Agent и при необходимости прокси-пул.
4. Не запрашивайте лишние права (least privilege).
5. Не парсите приватные данные/закрытые аккаунты.

---

## 7) Типовые проблемы и решения
1. **OAuth не подключается**  
   Почти всегда проблема в неверном Redirect URI (должен совпадать 1-в-1).
2. **Очередь не обрабатывается**  
   Проверьте, включен ли cron в FastPanel.
3. **Пустой контент**  
   Проверьте scopes, app review, статус приложения (live/production).
4. **Ошибки БД**  
   Проверьте параметры MySQL в `.env` и доступ пользователя к базе.
