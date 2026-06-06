<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;

$error = '';
Csrf::requirePost();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (Auth::attempt($email, $password)) {
        header('Location: index.php');
        exit;
    }
    $error = 'Неверный логин или пароль';
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container"><section class="card" style="max-width:460px;margin:60px auto">
<h2>Вход в админку</h2>
<?php if ($error !== ''): ?><p style="color:#fca5a5"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<form method="post" class="grid" style="grid-template-columns:1fr">
<input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
<label>Email<input name="email" required></label>
<label>Password<input name="password" type="password" required></label>
<button type="submit">Войти</button>
</form>
</section></main>
</body></html>
