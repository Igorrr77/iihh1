<?php ob_start(); ?>
<h1>Поиск</h1><form method="get" action="/search"><input name="q" value="<?= e($q) ?>" placeholder="Например: высокий сахар"><button>Найти</button></form>
<section><h2>Результаты</h2><div class="cards"><?php foreach ($results as $video): ?><?php include root_path('app/Views/partials/video-card.php'); ?><?php endforeach; ?></div></section>
<section><h2>Умный AI-поиск</h2><form id="ai-search-form"><input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><input name="query" placeholder="У меня высокий сахар и гепатоз, с чего начать?"><button>Спросить</button></form><pre id="ai-search-result"></pre></section>
<?php $content=ob_get_clean(); $title='Поиск'; include root_path('app/Views/layouts/main.php'); ?>
