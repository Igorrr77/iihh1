<?php

declare(strict_types=1);

return static function (PDO $pdo, array $input): void {
    $roles = [
        ['super_admin', 'Super Admin'],
        ['account_owner', 'Account Owner'],
        ['admin', 'Admin'],
        ['manager', 'Manager'],
        ['operator', 'Operator'],
        ['viewer', 'Viewer'],
    ];

    foreach ($roles as [$code, $name]) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO roles (code, name, is_system, created_at, updated_at) VALUES (:code,:name,1,NOW(),NOW())');
        $stmt->execute(['code' => $code, 'name' => $name]);
    }

    $accountName = $input['account_name'] ?? 'Default Account';
    $accountSlug = $input['account_slug'] ?? 'default-account';
    $stmt = $pdo->prepare('INSERT INTO accounts (name, slug, status, timezone, locale, created_at, updated_at) VALUES (:name,:slug,"active","UTC","ru",NOW(),NOW())');
    $stmt->execute(['name' => $accountName, 'slug' => $accountSlug]);
    $accountId = (int)$pdo->lastInsertId();

    $passwordHash = password_hash($input['admin_password'] ?? 'ChangeMe123!', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, first_name, status, created_at, updated_at) VALUES (:email,:password_hash,:first_name,"active",NOW(),NOW())');
    $stmt->execute([
        'email' => $input['admin_email'] ?? 'admin@example.com',
        'password_hash' => $passwordHash,
        'first_name' => $input['admin_name'] ?? 'Super',
    ]);
    $userId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO account_users (account_id, user_id, role_code, status, created_at, updated_at) VALUES (:account_id,:user_id,"account_owner","active",NOW(),NOW())');
    $stmt->execute(['account_id' => $accountId, 'user_id' => $userId]);
};
