<?php ob_start(); ?>
<h1><?= e($subcategory['title']) ?></h1><p>Раздел: <a href="<?= e(url('/' . $parent['slug'])) ?>"><?= e($parent['title']) ?></a></p><div class="cards"><?php foreach ($videos as $video): ?><?php include root_path('app/Views/partials/video-card.php'); ?><?php endforeach; ?></div>
<?php $content=ob_get_clean(); $title=$subcategory['title']; include root_path('app/Views/layouts/main.php'); ?>
