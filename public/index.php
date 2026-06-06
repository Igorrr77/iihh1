<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Csrf;
use App\Core\Queue;
use App\Core\ScheduleRepository;
use App\Core\SourceRepository;

Auth::require();
Csrf::requirePost();
$pdo = Database::pdo();
$queue = new Queue($pdo);
$sources = new SourceRepository($pdo);
$schedules = new ScheduleRepository($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_source') {
    $mode = $_POST['mode'] === 'author' ? 'author' : 'topic';
    $contentTypes = $_POST['content_types'] ?? ['post'];
    $sourceId = $sources->create([
        'provider' => trim($_POST['provider'] ?? ''),
        'account_handle' => trim($_POST['account_handle'] ?? ''),
        'mode' => $mode,
        'query_text' => trim($_POST['query_text'] ?? ''),
        'content_types' => array_values(array_filter($contentTypes, static fn ($t) => is_string($t))),
        'filters' => [
            'min_likes' => (int) ($_POST['min_likes'] ?? 0),
            'min_comments' => (int) ($_POST['min_comments'] ?? 0),
            'min_views' => (int) ($_POST['min_views'] ?? 0),
        ],
    ]);

    $interval = max(1, (int) ($_POST['interval_minutes'] ?? 15));
    $schedules->create($sourceId, $interval, 'UTC');

    $source = $sources->byId($sourceId);
    if ($source) {
        $queue->push('pull_content', [
            'source_id' => $sourceId,
            'provider' => $source['provider'],
            'account_handle' => $source['account_handle'],
            'mode' => $source['mode'],
            'query_text' => $source['query_text'],
            'content_types' => $source['content_types'],
            'filters' => $source['filters'],
        ]);
    }

    header('Location: index.php');
    exit;
}

$providerFilter = trim($_GET['provider'] ?? '');
$contentTypeFilter = trim($_GET['content_type'] ?? '');
$qFilter = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($providerFilter !== '') {
    $where[] = 'source = :provider';
    $params[':provider'] = $providerFilter;
}
if ($contentTypeFilter !== '') {
    $where[] = 'content_type = :content_type';
    $params[':content_type'] = $contentTypeFilter;
}
if ($qFilter !== '') {
    $where[] = '(title LIKE :q OR body LIKE :q)';
    $params[':q'] = '%' . $qFilter . '%';
}

$sql = 'SELECT id, source, content_type, external_id, author, title, body, popularity_json, published_at, fetched_at FROM content_items';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$jobs = $pdo->query('SELECT id, type, status, attempts, max_attempts, updated_at, last_error FROM jobs ORDER BY id DESC LIMIT 30')->fetchAll();
$sourceList = $sources->all();
$scheduleList = $schedules->all();

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Social Harvester Admin</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
        <h1>Social Harvester Admin</h1>
        <div>
            <a href="oauth.php" style="color:#93c5fd">OAuth</a>
            &nbsp;|&nbsp;
            <a href="guides.php" style="color:#93c5fd">Гайд подключений</a>
            &nbsp;|&nbsp;
            <a href="doctor.php" style="color:#93c5fd">Проверка системы</a>
            &nbsp;|&nbsp;
            <a href="export.php?provider=<?= urlencode($providerFilter) ?>&content_type=<?= urlencode($contentTypeFilter) ?>&q=<?= urlencode($qFilter) ?>" style="color:#93c5fd">CSV экспорт</a>
            &nbsp;|&nbsp;
            <a href="logout.php" style="color:#fca5a5">Выйти</a>
        </div>
    </div>

    <section class="card">
        <h2>Создать источник + планировщик</h2>
        <form method="post" class="grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="create_source">
            <label>Платформа
                <select name="provider" required>
                    <option value="facebook">Facebook</option><option value="instagram">Instagram</option>
                    <option value="tiktok">TikTok</option><option value="telegram">Telegram</option>
                    <option value="reddit">Reddit</option><option value="pinterest">Pinterest</option>
                    <option value="threads">Threads</option><option value="x">Twitter/X</option>
                </select>
            </label>
            <label>Mode
                <select name="mode"><option value="topic">По теме</option><option value="author">По автору</option></select>
            </label>
            <label>Автор/аккаунт
                <input name="account_handle" placeholder="@author или subreddit/channel" required>
            </label>
            <label>Тема/ключевик
                <input name="query_text" placeholder="AI, crypto, sport ...">
            </label>
            <label>Мин. лайков<input name="min_likes" type="number" min="0" value="0"></label>
            <label>Мин. комментариев<input name="min_comments" type="number" min="0" value="0"></label>
            <label>Мин. просмотров<input name="min_views" type="number" min="0" value="0"></label>
            <label>Интервал, минут<input name="interval_minutes" type="number" min="1" value="15"></label>
            <label><input type="checkbox" name="content_types[]" value="post" checked> Посты</label>
            <label><input type="checkbox" name="content_types[]" value="comment"> Комментарии</label>
            <label><input type="checkbox" name="content_types[]" value="video"> Видео</label>
            <label><input type="checkbox" name="content_types[]" value="reel"> Reels</label>
            <button type="submit">Создать и запустить</button>
        </form>
    </section>

    <section class="card">
        <h2>Фильтры админки</h2>
        <form method="get" class="grid">
            <label>Платформа<input name="provider" value="<?= htmlspecialchars($providerFilter, ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Тип контента<input name="content_type" value="<?= htmlspecialchars($contentTypeFilter, ENT_QUOTES, 'UTF-8') ?>"></label>
            <label>Текст/тема<input name="q" value="<?= htmlspecialchars($qFilter, ENT_QUOTES, 'UTF-8') ?>"></label>
            <button type="submit">Применить</button>
        </form>
    </section>

    <section class="card"><h2>Источники</h2><table><thead><tr><th>ID</th><th>Provider</th><th>Mode</th><th>Handle</th><th>Query</th></tr></thead><tbody>
        <?php foreach ($sourceList as $s): ?><tr><td><?= (int) $s['id'] ?></td><td><?= htmlspecialchars($s['provider'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($s['mode'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($s['account_handle'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string) $s['query_text'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
    </tbody></table></section>

    <section class="card"><h2>Планировщик</h2><table><thead><tr><th>ID</th><th>Source</th><th>Cron</th><th>Next run</th></tr></thead><tbody>
        <?php foreach ($scheduleList as $s): ?><tr><td><?= (int) $s['id'] ?></td><td><?= htmlspecialchars($s['provider'] . ' / ' . $s['account_handle'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($s['cron_expr'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($s['next_run_at'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
    </tbody></table></section>

    <section class="card"><h2>Очередь</h2><table><thead><tr><th>ID</th><th>Тип</th><th>Статус</th><th>Попытки</th><th>Ошибка</th></tr></thead><tbody>
        <?php foreach ($jobs as $job): ?><tr><td><?= (int) $job['id'] ?></td><td><?= htmlspecialchars($job['type'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($job['status'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $job['attempts'] ?>/<?= (int) $job['max_attempts'] ?></td><td><?= htmlspecialchars((string) ($job['last_error'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
    </tbody></table></section>

    <section class="card"><h2>Контент</h2><table><thead><tr><th>ID</th><th>Source</th><th>Type</th><th>Author</th><th>Title</th><th>Body</th><th>Popularity</th></tr></thead><tbody>
        <?php foreach ($items as $item): $pop = json_decode((string) $item['popularity_json'], true) ?: []; ?>
            <tr><td><?= (int) $item['id'] ?></td><td><?= htmlspecialchars($item['source'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($item['content_type'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string) $item['author'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars(mb_strimwidth((string) $item['title'], 0, 40, '...'), ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars(mb_strimwidth((string) $item['body'], 0, 70, '...'), ENT_QUOTES, 'UTF-8') ?></td><td>❤️<?= (int) ($pop['likes'] ?? 0) ?> 💬<?= (int) ($pop['comments'] ?? 0) ?> 👁<?= (int) ($pop['views'] ?? 0) ?></td></tr>
        <?php endforeach; ?>
    </tbody></table></section>
</main>
</body>
</html>
