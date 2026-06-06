<?php

declare(strict_types=1);

namespace App\Core;

final class OAuthService
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly OAuthStateRepository $states,
        private readonly OAuthAccountRepository $accounts,
    ) {
    }

    public function begin(string $provider, array $scopes): string
    {
        $cfg = OAuthProviderRegistry::config($provider);
        if ($cfg === [] || $cfg['client_id'] === '') {
            throw new \RuntimeException('Provider config missing');
        }

        $state = bin2hex(random_bytes(16));
        $verifier = null;
        $challenge = null;
        if ($cfg['pkce']) {
            $verifier = bin2hex(random_bytes(32));
            $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        }

        $this->states->create($provider, $state, $cfg['redirect_uri'], $scopes, $verifier);
        $scopeStr = implode($cfg['scopes_delimiter'], $scopes);

        $params = [
            'client_id' => $cfg['client_id'],
            'redirect_uri' => $cfg['redirect_uri'],
            'response_type' => 'code',
            'state' => $state,
            'scope' => $scopeStr,
        ];

        if ($cfg['pkce']) {
            $params['code_challenge'] = $challenge;
            $params['code_challenge_method'] = 'S256';
        }

        return $cfg['authorize'] . '?' . http_build_query($params);
    }

    public function callback(string $provider, string $state, string $code): array
    {
        $cfg = OAuthProviderRegistry::config($provider);
        $saved = $this->states->consume($state);
        if (!$saved || $saved['provider'] !== $provider) {
            throw new \RuntimeException('Invalid state');
        }

        $fields = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $saved['redirect_uri'],
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
        ];
        if (!empty($saved['code_verifier'])) {
            $fields['code_verifier'] = $saved['code_verifier'];
        }

        $resp = $this->http->request('POST', $cfg['token'], [], null, $fields);
        $data = $resp['data'];
        $accessToken = (string) ($data['access_token'] ?? '');
        if ($accessToken === '') {
            throw new \RuntimeException('Token exchange failed');
        }

        $providerUserId = (string) ($data['user_id'] ?? ($data['sub'] ?? ('acct_' . substr(hash('sha256', $accessToken), 0, 16))));
        $scopes = ScopeManager::normalize((string) ($saved['requested_scopes'] ?? ''));

        $this->accounts->upsert(
            $provider,
            $providerUserId,
            $provider . ':' . $providerUserId,
            $scopes,
            $accessToken,
            $data['refresh_token'] ?? null,
            isset($data['expires_in']) ? (int) $data['expires_in'] : null,
            isset($data['refresh_expires_in']) ? (int) $data['refresh_expires_in'] : null,
            (string) ($data['token_type'] ?? 'Bearer')
        );

        return $data;
    }

    public function refreshIfNeeded(string $provider): void
    {
        $account = $this->accounts->findActive($provider);
        if (!$account || empty($account['refresh_token']) || empty($account['expires_at'])) {
            return;
        }

        $expiresAt = strtotime((string) $account['expires_at']);
        if ($expiresAt === false || $expiresAt > (time() + 600)) {
            return;
        }

        $cfg = OAuthProviderRegistry::config($provider);
        if ($cfg === []) {
            return;
        }

        $resp = $this->http->request('POST', $cfg['token'], [], null, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $account['refresh_token'],
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
        ]);
        $data = $resp['data'];
        if (empty($data['access_token'])) {
            return;
        }

        $this->accounts->upsert(
            $provider,
            (string) $account['provider_user_id'],
            (string) ($account['account_label'] ?? ''),
            $account['scopes'] ?? [],
            (string) $data['access_token'],
            $data['refresh_token'] ?? $account['refresh_token'],
            isset($data['expires_in']) ? (int) $data['expires_in'] : 3600,
            isset($data['refresh_expires_in']) ? (int) $data['refresh_expires_in'] : null,
            (string) ($data['token_type'] ?? 'Bearer')
        );
    }
}
