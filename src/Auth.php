<?php

declare(strict_types=1);

namespace App;

final class Auth
{
    public function __construct(private Database $db)
    {
    }

    public function login(string $email, string $password): bool
    {
        $user = $this->db->query(
            'SELECT id, password_hash, tenant_id FROM users WHERE email = :email LIMIT 1',
            ['email' => $email]
        )->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['tenant_id'] = (int) $user['tenant_id'];

        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function tenantId(): ?int
    {
        return $_SESSION['tenant_id'] ?? null;
    }

    public function requireAuth(): void
    {
        if (!$this->userId()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }
}
