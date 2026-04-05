<?php ob_start(); ?>
<article class="video-page"><h1><?= e($video['title']) ?></h1><div class="embed-wrap"><iframe src="<?= e($video['embed_url']) ?>" title="<?= e($video['title']) ?>" allowfullscreen loading="lazy"></iframe></div><p><?= nl2br(e($video['ai_summary'] ?: $video['description'])) ?></p><ul><li>Дата: <?= e((string)$video['published_at']) ?></li><li>Длительность: <?= e((string)$video['duration_seconds']) ?> сек</li></ul></article>
<section><h2>Похожие видео</h2><div class="cards"><?php foreach ($related as $video): ?><?php include root_path('app/Views/partials/video-card.php'); ?><?php endforeach; ?></div></section>
<?php $content=ob_get_clean(); $title=$video['title']; include root_path('app/Views/layouts/main.php'); ?>
