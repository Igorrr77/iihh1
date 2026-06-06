<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class SystemCheck
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function run(): array
    {
        $checks = [];

        $checks[] = $this->checkPhp();
        $checks[] = $this->checkDb();
        $checks[] = $this->checkTables();
        $checks[] = $this->checkAppUrl();
        $checks[] = $this->checkAppKey();
        $checks[] = $this->checkAntiBan();
        $checks = array_merge($checks, $this->checkOAuthProviders());

        return $checks;
    }

    private function checkPhp(): array
    {
        return ['name' => 'PHP >= 8.2', 'ok' => version_compare(PHP_VERSION, '8.2.0', '>='), 'info' => PHP_VERSION, 'severity' => 'high'];
    }

    private function checkDb(): array
    {
        try {
            $this->pdo->query('SELECT 1');
            return ['name' => 'DB connection', 'ok' => true, 'info' => 'ok', 'severity' => 'high'];
        } catch (Throwable $e) {
            return ['name' => 'DB connection', 'ok' => false, 'info' => $e->getMessage(), 'severity' => 'high'];
        }
    }

    private function checkTables(): array
    {
        try {
            $required = ['sources', 'schedules', 'content_items', 'jobs', 'run_logs', 'oauth_states', 'oauth_accounts'];
            $stmt = $this->pdo->query('SHOW TABLES');
            $rows = $stmt->fetchAll();
            $flat = [];
            foreach ($rows as $row) {
                foreach ($row as $value) {
                    $flat[] = (string) $value;
                }
            }
            $missing = array_values(array_diff($required, array_unique($flat)));

            return ['name' => 'Required tables', 'ok' => $missing === [], 'info' => $missing === [] ? 'all present' : 'missing: ' . implode(', ', $missing), 'severity' => 'high'];
        } catch (Throwable $e) {
            return ['name' => 'Required tables', 'ok' => false, 'info' => $e->getMessage(), 'severity' => 'high'];
        }
    }

    private function checkAppUrl(): array
    {
        $url = (string) Env::get('APP_URL', '');
        $ok = str_starts_with($url, 'https://');
        return ['name' => 'APP_URL uses HTTPS', 'ok' => $ok, 'info' => $url !== '' ? $url : 'APP_URL is empty', 'severity' => 'high'];
    }

    private function checkAppKey(): array
    {
        $key = (string) Env::get('APP_KEY', '');
        $ok = strlen($key) >= 32;
        return ['name' => 'APP_KEY length', 'ok' => $ok, 'info' => 'length=' . strlen($key), 'severity' => 'medium'];
    }

    private function checkAntiBan(): array
    {
        $proxyCount = count(Env::csv('PROXY_POOL'));
        $uaCount = count(Env::split('USER_AGENT_POOL'));
        $ok = $uaCount > 0;
        $info = 'proxy_pool=' . $proxyCount . ', user_agents=' . $uaCount;
        if ($proxyCount === 0) {
            $info .= ' (warning: no proxy pool)';
        }

        return ['name' => 'Anti-ban config', 'ok' => $ok, 'info' => $info, 'severity' => 'medium'];
    }

    private function checkOAuthProviders(): array
    {
        $providers = ['facebook', 'instagram', 'threads', 'x', 'tiktok', 'pinterest', 'reddit'];
        $result = [];

        foreach ($providers as $provider) {
            $cfg = OAuthProviderRegistry::config($provider);
            $ok = $cfg !== [] && ($cfg['client_id'] ?? '') !== '' && ($cfg['client_secret'] ?? '') !== '' && ($cfg['redirect_uri'] ?? '') !== '';
            $result[] = [
                'name' => 'OAuth config: ' . $provider,
                'ok' => $ok,
                'info' => $ok ? 'configured' : 'set client_id + client_secret + APP_URL',
                'severity' => 'medium',
            ];
        }

        return $result;
    }
}
