<?php ob_start(); ?>
<h1>Dashboard</h1><div class="stats"><article class="card"><h2>Всего видео</h2><p><?= (int)$stats['videos'] ?></p></article><article class="card"><h2>Длинных</h2><p><?= (int)$stats['long_videos'] ?></p></article><article class="card"><h2>Manual review</h2><p><?= (int)$stats['review'] ?></p></article><article class="card"><h2>AI jobs</h2><p><?= (int)$stats['jobs'] ?></p></article></div>
<?php $content=ob_get_clean(); $title='Админка'; include root_path('app/Views/layouts/admin.php'); ?>
