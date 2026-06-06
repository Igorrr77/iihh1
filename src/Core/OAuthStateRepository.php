<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class OAuthStateRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(string $provider, string $state, string $redirectUri, array $scopes, ?string $verifier): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO oauth_states(provider, state_token, code_verifier, redirect_uri, requested_scopes, created_at, expires_at)
            VALUES(:provider, :state_token, :code_verifier, :redirect_uri, :requested_scopes, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE))');
        $stmt->execute([
            ':provider' => $provider,
            ':state_token' => $state,
            ':code_verifier' => $verifier,
            ':redirect_uri' => $redirectUri,
            ':requested_scopes' => implode(' ', $scopes),
        ]);
    }

    public function consume(string $state): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM oauth_states WHERE state_token = :state AND expires_at >= UTC_TIMESTAMP() LIMIT 1');
        $stmt->execute([':state' => $state]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $del = $this->pdo->prepare('DELETE FROM oauth_states WHERE id = :id');
        $del->execute([':id' => $row['id']]);
        return $row;
    }
}
