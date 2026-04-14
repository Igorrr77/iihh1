<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public function __construct(private readonly Session $session, private readonly string $tokenName)
    {
    }

    public function token(): string
    {
        $token = $this->session->get($this->tokenName);
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $this->session->set($this->tokenName, $token);
        }
        return $token;
    }

    public function validate(?string $token): bool
    {
        return hash_equals((string)$this->session->get($this->tokenName, ''), (string)$token);
    }
}
