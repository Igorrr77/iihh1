<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if ($argc < 4) {
    echo "Usage: php scripts/create_admin.php <tenant_id> <email> <password>\n";
    exit(1);
}

[$_, $tenantId, $email, $password] = $argv;
$hash = password_hash($password, PASSWORD_DEFAULT);

$db->query('INSERT INTO users (tenant_id, email, password_hash, role, created_at) VALUES (:tenant_id, :email, :password_hash, :role, UTC_TIMESTAMP())', [
    'tenant_id' => (int) $tenantId,
    'email' => $email,
    'password_hash' => $hash,
    'role' => 'owner',
]);

echo "Admin created\n";
