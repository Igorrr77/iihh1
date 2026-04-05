<article class="video-card">
  <a href="/video/<?= e($video['youtube_video_id']) ?>" class="thumb-link">
    <img src="<?= e($video['thumbnail_high'] ?: '/assets/images/fallback-thumb.svg') ?>" alt="<?= e($video['title']) ?>" loading="lazy">
  </a>
  <h3><a href="/video/<?= e($video['youtube_video_id']) ?>"><?= e($video['title']) ?></a></h3>
  <p><?= e($video['ai_summary'] ?: mb_substr(strip_tags((string)$video['description']), 0, 140) . '...') ?></p>
  <div class="badges">
    <?php if ((int)($video['is_start_here'] ?? 0) === 1): ?><span>Важно</span><?php endif; ?>
    <?php if ((int)($video['is_faq'] ?? 0) === 1): ?><span>FAQ</span><?php endif; ?>
    <?php if ((int)($video['is_story'] ?? 0) === 1): ?><span>История</span><?php endif; ?>
  </div>
  <a class="btn" href="<?= e($video['url']) ?>" target="_blank" rel="noopener">Смотреть</a>
</article>
