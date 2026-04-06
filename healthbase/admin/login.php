<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
$db = (new App\Core\Database(config('database')))->pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_token'] ?? null)) {
        $error = 'Ошибка CSRF';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_user_id'] = (int)$user['id'];
            $db->prepare('UPDATE users SET last_login_at = :at WHERE id = :id')->execute(['at' => gmdate('Y-m-d H:i:s'), 'id' => $user['id']]);
            header('Location: ' . url('/admin/dashboard'));
            exit;
        }
        $error = 'Неверный логин или пароль';
    }
}
?><!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>"><title>Вход в админку</title></head>
<body class="auth-page"><main class="container"><h1>Вход администратора</h1><?php if (!empty($error)): ?><p class="error"><?= e($error) ?></p><?php endif; ?><form method="post" class="card"><input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><label>Email<input type="email" name="email" required></label><label>Пароль<input type="password" name="password" required></label><button class="btn">Войти</button></form></main></body></html>
