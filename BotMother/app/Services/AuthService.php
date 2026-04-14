<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserRepository;

final class AuthService
{
    public function __construct(private readonly UserRepository $users, private readonly Session $session)
    {
    }

    public function login(string $email, string $password, int $accountId): ?array
    {
        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        $membership = $this->users->findAccountRole((int)$user['id'], $accountId);
        if (!$membership || $membership['status'] !== 'active') {
            return null;
        }

        $this->session->regenerateId();
        $this->session->set('auth', [
            'user_id' => (int)$user['id'],
            'account_id' => $accountId,
            'role_code' => $membership['role_code'],
        ]);

        return $this->session->get('auth');
    }

    public function logout(): void
    {
        $this->session->destroy();
    }

    public function auth(): ?array
    {
        $auth = $this->session->get('auth');
        return is_array($auth) ? $auth : null;
    }
}
