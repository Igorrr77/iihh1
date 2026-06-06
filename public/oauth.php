<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Csrf;
use App\Core\HttpClient;
use App\Core\OAuthAccountRepository;
use App\Core\OAuthService;
use App\Core\OAuthStateRepository;
use App\Core\ScopeManager;

Auth::require();
Csrf::requirePost();
$pdo = Database::pdo();
$service = new OAuthService(new HttpClient(), new OAuthStateRepository($pdo), new OAuthAccountRepository($pdo));

$provider = strtolower(trim($_GET['provider'] ?? $_POST['provider'] ?? ''));
$action = trim($_GET['action'] ?? $_POST['action'] ?? 'list');
$message = '';
$error = '';

try {
    if ($action === 'start' && $provider !== '') {
        $scopeInput = trim($_POST['scopes'] ?? '');
        $scopes = $scopeInput !== '' ? ScopeManager::normalize($scopeInput) : ScopeManager::defaults($provider);
        $url = $service->begin($provider, $scopes);
        header('Location: ' . $url);
        exit;
    }

    if ($action === 'callback' && $provider !== '') {
        $state = trim($_GET['state'] ?? '');
        $code = trim($_GET['code'] ?? '');
        $service->callback($provider, $state, $code);
        $message = 'OAuth подключение сохранено для ' . $provider;
    }

    if ($action === 'refresh' && $provider !== '') {
        $service->refreshIfNeeded($provider);
        $message = 'Проверка refresh выполнена для ' . $provider;
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$accounts = (new OAuthAccountRepository($pdo))->all();
$providers = ['facebook', 'instagram', 'threads', 'x', 'tiktok', 'pinterest', 'reddit'];
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>OAuth</title><link rel="stylesheet" href="assets/style.css"></head>
<body><main class="container">
<div style="display:flex;justify-content:space-between"><h1>OAuth подключения</h1><a href="index.php" style="color:#93c5fd">← в админку</a></div>
<?php if ($message !== ''): ?><div class="card" style="border-color:#14532d"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="card" style="border-color:#7f1d1d"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<section class="card"><h2>Подключить аккаунт</h2>
<?php foreach ($providers as $p): ?>
<form method="post" class="grid" style="grid-template-columns:2fr 3fr 1fr; margin-bottom:8px">
<input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="start"><input type="hidden" name="provider" value="<?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>">
<label><?= strtoupper($p) ?><input name="scopes" placeholder="Scopes (пусто = default)"></label>
<div style="align-self:end;color:#9ca3af">Рекомендуемые: <?= htmlspecialchars(implode(' ', ScopeManager::defaults($p)), ENT_QUOTES, 'UTF-8') ?></div>
<button type="submit">Подключить</button>
</form>
<form method="post" style="margin-bottom:14px"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="refresh"><input type="hidden" name="provider" value="<?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>"><button type="submit">Refresh <?= strtoupper($p) ?></button></form>
<?php endforeach; ?>
</section>

<section class="card"><h2>Token vault</h2>
<table><thead><tr><th>ID</th><th>Provider</th><th>User</th><th>Scopes</th><th>Expires</th><th>Status</th></tr></thead><tbody>
<?php foreach ($accounts as $a): ?><tr><td><?= (int) $a['id'] ?></td><td><?= htmlspecialchars($a['provider'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string) $a['provider_user_id'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string) $a['scopes_json'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string) $a['expires_at'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string) $a['status'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
</tbody></table>
</section>
</main></body></html>
