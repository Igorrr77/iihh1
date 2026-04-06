<?php ob_start(); ?>
<article class="video-page"><h1><?= e($video['title']) ?></h1><div class="embed-wrap"><iframe src="<?= e($video['embed_url']) ?>" title="<?= e($video['title']) ?>" allow="autoplay; encrypted-media" loading="lazy"></iframe></div></article>
<section><h2>Похожие видео</h2><div class="cards"><?php foreach (array_slice($related, 0, max(3, count($related))) as $video): ?><?php include root_path('app/Views/partials/video-card.php'); ?><?php endforeach; ?></div></section>
<?php $content=ob_get_clean(); $title=$video['title']; include root_path('app/Views/layouts/main.php'); ?>
