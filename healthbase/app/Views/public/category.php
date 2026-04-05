<?php ob_start(); ?>
<h1><?= e($category['title']) ?></h1><p><?= e($category['description']) ?></p><div class="cards"><?php foreach ($videos as $video): ?><?php include root_path('app/Views/partials/video-card.php'); ?><?php endforeach; ?></div>
<?php $content=ob_get_clean(); $title=$category['title']; include root_path('app/Views/layouts/main.php'); ?>
