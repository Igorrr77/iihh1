<?php ob_start(); ?>
<section class="hero"><h1>База знаний Международного Института Здоровья Человека</h1><p>Удобный навигатор по длинным видео для людей 60+.</p><a href="/start" class="btn btn-lg">С чего начать</a></section>
<section><h2>Ключевые разделы</h2><div class="pill-grid"><?php foreach ($categories as $cat): ?><a class="pill" href="/<?= e($cat['slug']) ?>"><?= e($cat['title']) ?></a><?php endforeach; ?></div></section>
<section><h2>Новые видео</h2><div class="cards"><?php foreach ($videos as $video): ?><?php include root_path('app/Views/partials/video-card.php'); ?><?php endforeach; ?></div></section>
<?php $content = ob_get_clean(); $title='Главная'; include root_path('app/Views/layouts/main.php'); ?>
