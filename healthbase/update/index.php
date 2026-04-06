<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
if (!isset($_SESSION['admin_user_id'])) {
    http_response_code(403);
    exit('Требуется авторизация администратора');
}

$db = (new App\Core\Database(config('database')))->pdo();
$sql = file_get_contents(root_path('database/migrations/001_initial_schema.sql'));
$db->exec($sql);
foreach (glob(root_path('storage/cache/*.json')) ?: [] as $cache) { @unlink($cache); }
file_put_contents(root_path('storage/update.log'), '[' . gmdate('c') . "] update applied\n", FILE_APPEND);

echo 'Обновление завершено';
