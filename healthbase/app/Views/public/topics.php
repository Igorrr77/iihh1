<?php ob_start(); ?>
<h1>Все темы</h1><div class="topic-list"><?php foreach ($categories as $cat): ?><article class="card"><h2><a href="/<?= e($cat['slug']) ?>"><?= e($cat['title']) ?></a></h2><p><?= e($cat['description']) ?></p></article><?php endforeach; ?></div>
<?php $content=ob_get_clean(); $title='Темы'; include root_path('app/Views/layouts/main.php'); ?>
