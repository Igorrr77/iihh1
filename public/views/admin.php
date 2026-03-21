<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sales Speech Intelligence</title>
  <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
<div class="container">
  <h1>Sales Speech Intelligence</h1>

  <section class="card" id="auth-card">
    <h2>Вход в админпанель</h2>
    <input id="email" placeholder="Email" />
    <input id="password" type="password" placeholder="Пароль" />
    <button id="login-btn">Войти</button>
    <button id="logout-btn">Выйти</button>
  </section>

  <section class="card">
    <h2>Контекст продукта (на каждого чатбота/тенанта)</h2>
    <textarea id="product-context" rows="8" placeholder="Вставьте описание продукта, УТП, FAQ, оффер, ограничения..."></textarea>
    <button id="save-context-btn">Сохранить контекст</button>
  </section>

  <section class="card">
    <h2>Live-подсказки продавцу</h2>
    <p>Обновляется каждые 4 секунды.</p>
    <div id="feed"></div>
  </section>

  <section class="card">
    <h2>Экспорт</h2>
    <a href="/api/export?format=json" target="_blank">JSON</a>
    <a href="/api/export?format=csv" target="_blank">CSV</a>
  </section>

  <section class="card">
    <h2>Интеграции (CRM + внешний webhook)</h2>
    <input id="crm-endpoint" placeholder="https://crm.example/webhook" />
    <input id="out-webhook" placeholder="https://your-app.example/incoming" />
    <button id="save-integrations-btn">Сохранить интеграции</button>
  </section>
</div>
<script src="/assets/app.js"></script>
</body>
</html>
