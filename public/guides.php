<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\Auth;

Auth::require();
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Интерактивный гайд подключений</title><link rel="stylesheet" href="assets/style.css"></head>
<body><main class="container">
<div style="display:flex;justify-content:space-between"><h1>Интерактивный гайд OAuth-подключений</h1><a href="index.php" style="color:#93c5fd">← в админку</a></div>
<section class="card"><h2>0) Общие принципы безопасности и anti-ban</h2>
<ul>
<li>Используйте только официальные API и scopes, необходимые для задачи (least privilege).</li>
<li>Соблюдайте rate limits и ставьте интервалы минимум 5–15 минут для большинства источников.</li>
<li>Не делайте массовые параллельные запросы к одному endpoint с одного IP.</li>
<li>Не собирайте приватный/закрытый контент, только публичные данные.</li>
<li>Храните client secret только на сервере, ротация ключей минимум раз в 90 дней.</li>
<li>Настройте IP allowlist в кабинетах провайдеров (если доступно).</li>
</ul></section>

<section class="card"><h2>1) Пошагово для каждого провайдера</h2>
<details open><summary><b>Meta (Facebook/Instagram/Threads)</b></summary>
<ol>
<li>Создайте приложение в Meta for Developers (тип Business).</li>
<li>Добавьте продукты: Facebook Login, Instagram Graph API, Threads API.</li>
<li>В Valid OAuth Redirect URIs добавьте:
<pre>/public/oauth.php?provider=facebook&action=callback
/public/oauth.php?provider=instagram&action=callback
/public/oauth.php?provider=threads&action=callback</pre></li>
<li>В App Review запросите нужные permissions (например, pages_read_engagement, instagram_basic, threads_basic).</li>
<li>Переключите app из Development в Live только после прохождения review.</li>
</ol></details>

<details><summary><b>Twitter/X</b></summary>
<ol>
<li>Создайте App в X Developer Portal, включите OAuth 2.0 PKCE.</li>
<li>Redirect URI: <code>/public/oauth.php?provider=x&action=callback</code>.</li>
<li>Scopes: <code>tweet.read users.read offline.access</code> + доп. по необходимости.</li>
<li>Включите refresh token и ограничьте доступ только чтением.</li>
</ol></details>

<details><summary><b>TikTok</b></summary>
<ol>
<li>Создайте app в TikTok for Developers.</li>
<li>Укажите Login Kit Redirect URI: <code>/public/oauth.php?provider=tiktok&action=callback</code>.</li>
<li>Запросите только read-only scopes для аналитики/контента.</li>
<li>Проверьте product-level approvals до запуска.</li>
</ol></details>

<details><summary><b>Pinterest</b></summary>
<ol>
<li>Создайте app в Pinterest Developers.</li>
<li>Redirect URI: <code>/public/oauth.php?provider=pinterest&action=callback</code>.</li>
<li>Scopes: <code>pins:read boards:read user_accounts:read</code>.</li>
</ol></details>

<details><summary><b>Reddit</b></summary>
<ol>
<li>Создайте "web app" на reddit apps.</li>
<li>Redirect URI: <code>/public/oauth.php?provider=reddit&action=callback</code>.</li>
<li>Scopes: <code>identity read history</code> (минимально требуемые).</li>
<li>Обязательно задайте корректный User-Agent.</li>
</ol></details>
</section>

<section class="card"><h2>2) Что вводить в кабинете Social Harvester</h2>
<ol>
<li>Откройте <a href="oauth.php" style="color:#93c5fd">OAuth подключения</a>.</li>
<li>Выберите провайдера и при необходимости укажите custom scopes.</li>
<li>Нажмите «Подключить», пройдите consent screen у провайдера.</li>
<li>После возврата проверьте, что аккаунт появился в таблице Token vault.</li>
<li>Запустите «Refresh» для проверки refresh lifecycle.</li>
</ol>
</section>

<section class="card"><h2>3) Проверка готовности к production</h2>
<ul>
<li>✅ Redirect URI совпадает 1-в-1 между провайдером и `.env`.</li>
<li>✅ Scope review пройден, app в Live/Production (где требуется).</li>
<li>✅ На сервере настроен HTTPS и корректное серверное время (NTP).</li>
<li>✅ Включены лимиты частоты и интервалы планировщика.</li>
<li>✅ Есть ротация client secret и backup процедуры.</li>
</ul>
</section>
</main></body></html>
