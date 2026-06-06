<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use Commentor\Installer;

$envExists = is_file(__DIR__ . '/.env');
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$envExists) {
    $adminUser = trim((string) ($_POST['admin_user'] ?? 'admin'));
    $adminPassword = (string) ($_POST['admin_password'] ?? '');
    $geminiApiKey = trim((string) ($_POST['gemini_api_key'] ?? ''));

    if ($adminPassword === '') {
        $error = 'Введите пароль администратора';
    } else {
        try {
            Installer::run($adminUser, $adminPassword, $geminiApiKey);
            $message = 'Установка завершена. Перейдите в /index.php';
            $envExists = true;
        } catch (Throwable $e) {
            $error = 'Ошибка установки: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commentor Installer</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f5f6fb;padding:40px}.box{max-width:560px;margin:0 auto;background:#fff;border:1px solid #d8deea;border-radius:12px;padding:20px}
        input{width:100%;padding:10px;border:1px solid #ccd5e3;border-radius:8px;margin:6px 0 12px}
        button{padding:10px 14px;background:#355fe3;color:#fff;border:none;border-radius:8px;cursor:pointer}
        .ok{background:#e8f8ec;padding:10px;border:1px solid #a3d6ae;border-radius:8px;margin-bottom:10px}
        .err{background:#ffe8e8;padding:10px;border:1px solid #f2adad;border-radius:8px;margin-bottom:10px}
    </style>
</head>
<body>
<div class="box">
    <h1>Установка Commentor</h1>
    <p>VPS/FastPanel friendly установка через install.php.</p>

    <?php if ($message): ?><div class="ok"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>

    <?php if ($envExists): ?>
        <p>.env уже существует. Если нужно переустановить — удалите .env и storage/commentor.sqlite</p>
    <?php else: ?>
        <form method="post">
            <label>Логин администратора</label>
            <input type="text" name="admin_user" value="admin" required>
            <label>Пароль администратора</label>
            <input type="password" name="admin_password" required>
            <label>Gemini API Key</label>
            <input type="text" name="gemini_api_key" placeholder="AIza..." required>
            <button type="submit">Установить Commentor</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
