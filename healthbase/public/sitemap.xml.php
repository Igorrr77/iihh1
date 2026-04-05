<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');

$base = rtrim((string)(getenv('APP_URL') ?: 'http://localhost'), '/');
$static = ['/', '/start', '/topics', '/videos', '/search'];

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach ($static as $path) {
    echo '<url><loc>' . htmlspecialchars($base . $path, ENT_QUOTES, 'UTF-8') . '</loc></url>';
}
if (is_file(root_path('.env')) && is_file(root_path('storage/install.lock'))) {
    try {
        $pdo = (new App\Core\Database(config('database')))->pdo();
        $cats = $pdo->query('SELECT slug, updated_at FROM categories WHERE is_active = 1')->fetchAll();
        foreach ($cats as $cat) {
            echo '<url><loc>' . htmlspecialchars($base . '/' . $cat['slug'], ENT_QUOTES, 'UTF-8') . '</loc><lastmod>' . gmdate('c', strtotime($cat['updated_at'])) . '</lastmod></url>';
        }
        $videos = $pdo->query('SELECT youtube_video_id, updated_at FROM videos WHERE is_public = 1 AND is_long_video = 1 ORDER BY updated_at DESC LIMIT 5000')->fetchAll();
        foreach ($videos as $video) {
            echo '<url><loc>' . htmlspecialchars($base . '/video/' . $video['youtube_video_id'], ENT_QUOTES, 'UTF-8') . '</loc><lastmod>' . gmdate('c', strtotime($video['updated_at'])) . '</lastmod></url>';
        }
    } catch (Throwable) {
        // Keep sitemap operational even if DB is unavailable
    }
}

echo '</urlset>';
