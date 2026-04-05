<?php ob_start(); ?>
<h1>С чего начать</h1>
<p>Выберите направление и начните с проверенных видео.</p>
<div class="cards"><?php foreach ($videos as $video): if ((int)$video['is_start_here'] !== 1) continue; ?><?php include root_path('app/Views/partials/video-card.php'); ?><?php endforeach; ?></div>
<?php $content=ob_get_clean(); $title='С чего начать'; include root_path('app/Views/layouts/main.php'); ?>
