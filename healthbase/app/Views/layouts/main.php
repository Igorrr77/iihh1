<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'База знаний') ?></title>
  <meta name="description" content="<?= e($metaDescription ?? 'Навигатор по медицинским видео') ?>">
  <link rel="canonical" href="<?= e(config('app')['url'] . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
  <link rel="stylesheet" href="/assets/css/app.css">
  <script defer src="/assets/js/app.js"></script>
</head>
<body>
<header class="site-header">
  <a href="/" class="logo">База знаний</a>
  <form action="/search" method="get" class="search-inline"><input name="q" placeholder="Поиск по базе знаний" value="<?= e($_GET['q'] ?? '') ?>"><button>Найти</button></form>
</header>
<main class="container"><?= $content ?? '' ?></main>
<nav class="bottom-nav">
  <a href="/">Главная</a><a href="/search">Поиск</a><a href="/topics">Темы</a><a href="/videos">Новые</a><a href="/start">Важно</a>
</nav>
</body>
</html>
