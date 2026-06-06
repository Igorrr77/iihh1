<?php

declare(strict_types=1);

namespace Commentor;

use PDO;

final class DiagnosticsService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function runForAccount(int $accountId): array
    {
        $account = $this->loadAccount($accountId);
        $platform = (string) $account['platform'];

        $steps = [];
        $steps[] = $this->okStep('Базовая проверка аккаунта', 'Аккаунт найден в базе и активен для диагностики.');

        $tokenEncrypted = trim((string) ($account['access_token'] ?? ''));
        if ($tokenEncrypted === '') {
            $steps[] = $this->failStep('Проверка access token', 'Токен отсутствует. Заполните access token в карточке аккаунта.');
            return ['account' => $account, 'platform' => $platform, 'steps' => $steps, 'summary' => 'Не хватает access token'];
        }

        try {
            $token = Crypto::decrypt($tokenEncrypted);
            $steps[] = $this->okStep('Проверка access token', 'Токен прочитан и успешно расшифрован.');
        } catch (\Throwable $e) {
            $steps[] = $this->failStep('Проверка access token', 'Токен не расшифровывается: ' . $e->getMessage());
            return ['account' => $account, 'platform' => $platform, 'steps' => $steps, 'summary' => 'Ошибка шифрования токена'];
        }

        if ($platform === 'instagram' || $platform === 'facebook' || $platform === 'facebook_page') {
            $metaSteps = $this->runMetaDiagnostics($account, $token);
            $steps = array_merge($steps, $metaSteps);
        } elseif ($platform === 'tiktok') {
            $tiktokSteps = $this->runTikTokDiagnostics($account, $token);
            $steps = array_merge($steps, $tiktokSteps);
        } else {
            $steps[] = $this->warnStep('Платформа', 'Платформа не поддерживает автоматическую диагностику: ' . $platform);
        }

        $failed = array_filter($steps, static fn(array $step): bool => $step['status'] === 'fail');
        $summary = $failed === []
            ? 'Все ключевые проверки пройдены. Можно тестировать боевой комментарий.'
            : 'Есть ошибки. Исправьте шаги со статусом FAIL.';

        return [
            'account' => $account,
            'platform' => $platform,
            'steps' => $steps,
            'summary' => $summary,
            'checked_at' => gmdate('Y-m-d H:i:s') . ' UTC',
        ];
    }

    private function runMetaDiagnostics(array $account, string $token): array
    {
        $steps = [];
        $verifyToken = Env::get('WEBHOOK_VERIFY_TOKEN', '');
        $appSecret = Env::get('META_APP_SECRET', '');
        $accountExternalId = trim((string) ($account['account_id'] ?? ''));

        $steps[] = $verifyToken !== ''
            ? $this->okStep('WEBHOOK_VERIFY_TOKEN', 'Токен верификации webhook задан в .env.')
            : $this->failStep('WEBHOOK_VERIFY_TOKEN', 'Не задан WEBHOOK_VERIFY_TOKEN в .env.');

        $steps[] = $appSecret !== ''
            ? $this->okStep('META_APP_SECRET', 'Секрет Meta приложения задан, подпись webhook можно проверять.')
            : $this->failStep('META_APP_SECRET', 'Не задан META_APP_SECRET в .env.');

        $me = $this->getJson('https://graph.facebook.com/' . rawurlencode(Env::get('META_GRAPH_VERSION', 'v22.0')) . '/me?fields=id,name&access_token=' . rawurlencode($token));
        if ($me['ok']) {
            $steps[] = $this->okStep('Meta token запрос /me', 'Token валиден. API вернул: ' . json_encode($me['json'], JSON_UNESCAPED_UNICODE));
        } else {
            $steps[] = $this->failStep('Meta token запрос /me', 'Token невалиден или нет прав: HTTP ' . $me['status'] . '. ' . $me['error']);
            return $steps;
        }

        if ($accountExternalId === '') {
            $steps[] = $this->failStep('Проверка account_id', 'В аккаунте не заполнен внешний ID (Page ID / IG User ID).');
            return $steps;
        }

        $accountCheck = $this->getJson('https://graph.facebook.com/' . rawurlencode(Env::get('META_GRAPH_VERSION', 'v22.0')) . '/' . rawurlencode($accountExternalId) . '?fields=id,name,username&access_token=' . rawurlencode($token));
        if ($accountCheck['ok']) {
            $steps[] = $this->okStep('Проверка account_id в Graph API', 'ID совпадает и доступен через токен: ' . json_encode($accountCheck['json'], JSON_UNESCAPED_UNICODE));
        } else {
            $steps[] = $this->failStep('Проверка account_id в Graph API', 'ID не читается этим токеном: HTTP ' . $accountCheck['status'] . '. ' . $accountCheck['error']);
        }

        $steps[] = $this->warnStep(
            'Финальный тест reply',
            'Для окончательной проверки оставьте тестовый комментарий под постом/видео и убедитесь, что статус в таблице комментариев стал posted.'
        );

        return $steps;
    }

    private function runTikTokDiagnostics(array $account, string $token): array
    {
        $steps = [];
        $metadata = json_decode((string) ($account['metadata_json'] ?? '{}'), true);
        $replyApiUrl = trim((string) ($metadata['reply_api_url'] ?? ''));

        if ($replyApiUrl === '') {
            $steps[] = $this->failStep('reply_api_url', 'В metadata_json не задан reply_api_url. Добавьте JSON вида {"reply_api_url":"https://..."}.');
            return $steps;
        }

        $steps[] = $this->okStep('reply_api_url', 'URL для TikTok reply найден: ' . $replyApiUrl);

        $probe = $this->probeEndpoint($replyApiUrl, ['Authorization: Bearer ' . $token]);
        if ($probe['ok']) {
            $steps[] = $this->okStep('Проверка доступности endpoint', 'Endpoint отвечает (HTTP ' . $probe['status'] . ').');
        } else {
            $steps[] = $this->failStep('Проверка доступности endpoint', 'Endpoint недоступен/ошибка авторизации: HTTP ' . $probe['status'] . '. ' . $probe['error']);
        }

        $steps[] = $this->warnStep(
            'Финальный тест reply',
            'Оставьте тестовый комментарий и проверьте статус. Если failed — смотрите error_message в таблице комментариев.'
        );

        return $steps;
    }

    /**
     * @return array{ok:bool,status:int,json:array<string,mixed>,error:string}
     */
    private function getJson(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $error !== '') {
            return ['ok' => false, 'status' => $status, 'json' => [], 'error' => $error];
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            return ['ok' => false, 'status' => $status, 'json' => [], 'error' => 'Некорректный JSON ответ'];
        }

        if ($status >= 400 || isset($json['error'])) {
            $err = is_array($json['error'] ?? null) ? ($json['error']['message'] ?? 'API error') : ($json['error'] ?? 'API error');
            return ['ok' => false, 'status' => $status, 'json' => $json, 'error' => (string) $err];
        }

        return ['ok' => true, 'status' => $status, 'json' => $json, 'error' => ''];
    }

    /**
     * @param array<int,string> $headers
     * @return array{ok:bool,status:int,error:string}
     */
    private function probeEndpoint(string $url, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ]);

        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '') {
            return ['ok' => false, 'status' => $status, 'error' => $error];
        }

        return ['ok' => $status > 0 && $status < 500, 'status' => $status, 'error' => $status >= 400 ? 'Проверьте токен или права доступа.' : ''];
    }

    private function loadAccount(int $accountId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM accounts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $accountId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new \RuntimeException('Аккаунт не найден');
        }

        return $row;
    }

    private function okStep(string $title, string $message): array
    {
        return ['status' => 'ok', 'title' => $title, 'message' => $message];
    }

    private function warnStep(string $title, string $message): array
    {
        return ['status' => 'warn', 'title' => $title, 'message' => $message];
    }

    private function failStep(string $title, string $message): array
    {
        return ['status' => 'fail', 'title' => $title, 'message' => $message];
    }
}
