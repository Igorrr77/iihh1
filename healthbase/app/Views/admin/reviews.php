<?php ob_start(); ?>
<h1>Manual review очередь</h1><table><thead><tr><th>ID</th><th>Видео</th><th>Статус</th><th>Комментарий</th></tr></thead><tbody><?php foreach($reviews as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><a href="/video/<?= e($r['youtube_video_id']) ?>"><?= e($r['title']) ?></a></td><td><?= e($r['review_status']) ?></td><td><?= e((string)$r['note']) ?></td></tr><?php endforeach; ?></tbody></table>
<?php $content=ob_get_clean(); $title='Manual review'; include root_path('app/Views/layouts/admin.php'); ?>
