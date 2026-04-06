<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'База знаний') ?></title>
  <meta name="description" content="<?= e($metaDescription ?? 'Навигатор по медицинским видео') ?>">
  <link rel="canonical" href="<?= e(config('app')['url'] . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
  <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
  <script>
    window.APP_BASE_PATH = <?= json_encode(app_base_path(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  </script>
  <script defer src="<?= e(url('/assets/js/app.js')) ?>"></script>
</head>
<body>
<header class="site-header">
  <a href="<?= e(url('/')) ?>" class="logo">База знаний</a>
  <form action="<?= e(url('/search')) ?>" method="get" class="search-inline"><input name="q" placeholder="Поиск по базе знаний" value="<?= e($_GET['q'] ?? '') ?>"><button>Найти</button></form>
</header>
<main class="container"><?= $content ?? '' ?></main>
<nav class="bottom-nav">
  <a href="<?= e(url('/')) ?>">Главная</a><a href="<?= e(url('/search')) ?>">Поиск</a><a href="<?= e(url('/topics')) ?>">Темы</a><a href="<?= e(url('/videos')) ?>">Новые</a><a href="<?= e(url('/start')) ?>">Важно</a>
</nav>
</body>
</html>
