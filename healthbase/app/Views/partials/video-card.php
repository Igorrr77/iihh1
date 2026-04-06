<article class="video-card">
  <a href="<?= e(url('/video/' . $video['youtube_video_id'])) ?>" class="thumb-link">
    <img src="<?= e($video['thumbnail_high'] ?: url('/assets/images/fallback-thumb.svg')) ?>" alt="<?= e($video['title']) ?>" loading="lazy">
  </a>
  <h3 class="video-title"><a href="<?= e(url('/video/' . $video['youtube_video_id'])) ?>"><?= e($video['title']) ?></a></h3>
  <div class="badges">
    <?php if ((int)($video['is_start_here'] ?? 0) === 1): ?><span>Важно</span><?php endif; ?>
    <?php if ((int)($video['is_faq'] ?? 0) === 1): ?><span>FAQ</span><?php endif; ?>
    <?php if ((int)($video['is_story'] ?? 0) === 1): ?><span>История</span><?php endif; ?>
  </div>
</article>
