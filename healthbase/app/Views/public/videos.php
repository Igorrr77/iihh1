<?php ob_start(); ?>
<h1>Все видео</h1>
<form method="get" class="filters"><input name="q" value="<?= e($query ?? '') ?>" placeholder="Поиск по названию"><button>Фильтровать</button></form>
<div class="cards"><?php foreach ($videos as $video): ?><?php include root_path('app/Views/partials/video-card.php'); ?><?php endforeach; ?></div>
<?php $content=ob_get_clean(); $title='Все видео'; include root_path('app/Views/layouts/main.php'); ?>
