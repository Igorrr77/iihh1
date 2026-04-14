<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends BaseRepository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function findAccountRole(int $userId, int $accountId): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM account_users WHERE user_id=:user_id AND account_id=:account_id LIMIT 1');
        $stmt->execute(['user_id' => $userId, 'account_id' => $accountId]);
        return $stmt->fetch() ?: null;
    }
}
