<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;
$role = $argv[3] ?? 'owner';

if (!is_string($email) || !is_string($password) || $email === '' || $password === '') {
    echo "Usage: php scripts/create_admin.php <email> <password> [role]\n";
    exit(1);
}

$pdo = Database::connection();
$stmt = $pdo->prepare('INSERT INTO admin_users (email, password_hash, role) VALUES (:email, :password_hash, :role)');
$stmt->execute([
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role' => $role,
]);

echo "Admin user created: {$email} ({$role})\n";
