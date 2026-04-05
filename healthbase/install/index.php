<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$lockFile = root_path('storage/install.lock');
if (is_file($lockFile)) {
    exit('Установщик заблокирован. Удалите storage/install.lock для повторной установки.');
}

$step = (int)($_GET['step'] ?? 1);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['install'] = array_merge($_SESSION['install'] ?? [], $_POST);
    header('Location: ?step=' . ($step + 1));
    exit;
}

function check_env(): array {
    $checks = [];
    $checks['php_82'] = PHP_VERSION_ID >= 80200;
    $checks['pdo_mysql'] = extension_loaded('pdo_mysql');
    $checks['curl'] = extension_loaded('curl');
    $checks['json'] = extension_loaded('json');
    $checks['mbstring'] = extension_loaded('mbstring');
    $checks['writable_storage'] = is_writable(root_path('storage'));
    return $checks;
}

if ($step === 4 && !empty($_SESSION['install'])) {
    $i = $_SESSION['install'];
    try {
        $pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $i['db_host'], $i['db_port'], $i['db_name']), $i['db_user'], $i['db_pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $sql = file_get_contents(root_path('database/migrations/001_initial_schema.sql'));
        $pdo->exec($sql);
        $GLOBALS['pdo'] = $pdo;
        require root_path('database/seeds/001_taxonomy_seed.php');
        $stmt = $pdo->prepare('INSERT INTO users (email,password_hash,role,is_active,created_at,updated_at) VALUES (:email,:hash,"admin",1,:now,:now) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), updated_at=VALUES(updated_at)');
        $stmt->execute(['email' => $i['admin_email'], 'hash' => password_hash($i['admin_password'], PASSWORD_DEFAULT), 'now' => gmdate('Y-m-d H:i:s')]);

        $env = "APP_ENV=production\nAPP_URL={$i['site_url']}\nDB_HOST={$i['db_host']}\nDB_PORT={$i['db_port']}\nDB_DATABASE={$i['db_name']}\nDB_USERNAME={$i['db_user']}\nDB_PASSWORD={$i['db_pass']}\nYOUTUBE_CHANNEL_ID={$i['youtube_channel_id']}\nYOUTUBE_API_KEY={$i['youtube_api_key']}\nGEMINI_API_KEY={$i['gemini_api_key']}\nGEMINI_MODEL_ID={$i['gemini_model_id']}\nCRON_TOKEN=" . bin2hex(random_bytes(24)) . "\n";
        file_put_contents(root_path('.env'), $env);
        file_put_contents($lockFile, gmdate('c'));
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/css/app.css"><title>Установка Healthbase</title></head><body><main class="container"><h1>Установщик Healthbase</h1><?php foreach($errors as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?>
<?php if($step===1): $checks=check_env(); ?><h2>Шаг 1. Проверка окружения</h2><ul><?php foreach($checks as $k=>$v): ?><li><?= e($k) ?>: <?= $v?'OK':'FAIL' ?></li><?php endforeach; ?></ul><a class="btn" href="?step=2">Далее</a>
<?php elseif($step===2): ?><h2>Шаг 2. База данных</h2><form method="post"><label>DB Host<input name="db_host" value="127.0.0.1" required></label><label>DB Port<input name="db_port" value="3306" required></label><label>DB Name<input name="db_name" value="healthbase" required></label><label>DB User<input name="db_user" required></label><label>DB Pass<input name="db_pass"></label><button>Сохранить и продолжить</button></form>
<?php elseif($step===3): ?><h2>Шаг 3. Админ и API</h2><form method="post"><label>Site URL<input name="site_url" required></label><label>YouTube Channel ID<input name="youtube_channel_id" required></label><label>YouTube API key<input name="youtube_api_key" required></label><label>Gemini API key<input name="gemini_api_key" required></label><label>Gemini model id<input name="gemini_model_id" value="gemini-3.1-flash-lite-preview" required></label><label>Admin email<input type="email" name="admin_email" required></label><label>Admin password<input type="password" name="admin_password" required></label><button>Установить</button></form>
<?php else: ?><h2>Готово</h2><p>Установка завершена. Установщик заблокирован.</p><p><a class="btn" href="/admin/login.php">Войти в админку</a></p><?php endif; ?></main></body></html>
