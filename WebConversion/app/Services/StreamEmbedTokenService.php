<?php

declare(strict_types=1);

namespace App\Services;

final class StreamEmbedTokenService
{
    public function createSignedToken(string $webinarId, string $origin, int $ttlSec = 900): string
    {
        $secret = (string) getenv('EMBED_TOKEN_SECRET');
        $exp = time() + max(60, $ttlSec);
        $payload = json_encode([
            'webinar_id' => $webinarId,
            'origin' => $origin,
            'exp' => $exp,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($secret === '' || !is_string($payload)) {
            return '';
        }

        $b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $b64, $secret);
        return $b64 . '.' . $sig;
    }
}
