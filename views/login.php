<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commentor Admin Login</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="container narrow">
    <h1>Commentor</h1>
    <p>Вход в админ-панель</p>
    <?php if (!empty($loginError)): ?>
        <div class="alert error"><?= e($loginError) ?></div>
    <?php endif; ?>
    <form method="post" action="/index.php">
        <input type="hidden" name="action" value="login">
        <label>Логин<input type="text" name="login" required></label>
        <label>Пароль<input type="password" name="password" required></label>
        <button type="submit">Войти</button>
    </form>
</div>
</body>
</html>
