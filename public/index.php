<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use Commentor\Crypto;
use Commentor\Database;
use Commentor\DiagnosticsService;
use Commentor\Env;
use Commentor\Logger;
use Commentor\SettingRepository;

session_start();

$installed = is_file(dirname(__DIR__) . '/.env');
if (!$installed) {
    redirect('/install.php');
}

$action = $_GET['action'] ?? 'dashboard';
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    redirect('/index.php');
}

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_lock_until'] = 0;
}

if (empty($_SESSION['admin_auth'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
        if (time() < (int) $_SESSION['login_lock_until']) {
            $loginError = 'Слишком много попыток входа. Повторите позже.';
            require dirname(__DIR__) . '/views/login.php';
            exit;
        }

        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $envUser = Env::get('ADMIN_USER', 'admin');
        $envHash = Env::get('ADMIN_PASSWORD_HASH', '');

        if ($login === $envUser && $envHash !== null && password_verify($password, $envHash)) {
            $_SESSION['admin_auth'] = true;
            $_SESSION['login_attempts'] = 0;
            $_SESSION['login_lock_until'] = 0;
            session_regenerate_id(true);
            redirect('/index.php');
        }

        $_SESSION['login_attempts'] = (int) $_SESSION['login_attempts'] + 1;
        if ((int) $_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_lock_until'] = time() + 300;
            $_SESSION['login_attempts'] = 0;
        }

        $loginError = 'Неверный логин или пароль';
    }

    require dirname(__DIR__) . '/views/login.php';
    exit;
}

$pdo = Database::connection();
$logger = new Logger($pdo);
$settingsRepo = new SettingRepository($pdo);
$diagnosticReport = $_SESSION['diagnostic_report'] ?? null;
unset($_SESSION['diagnostic_report']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('CSRF token mismatch');
    }

    if (($_POST['action'] ?? '') === 'save_settings') {
        $settingsRepo->set('system_prompt', trim((string) ($_POST['system_prompt'] ?? '')));
        $settingsRepo->set('cta_link', trim((string) ($_POST['cta_link'] ?? 'https://028.uno/diag')));
        $settingsRepo->set('response_language', trim((string) ($_POST['response_language'] ?? 'ru')));
        $logger->info('Settings updated');
        redirect('/index.php?saved=1');
    }

    if (($_POST['action'] ?? '') === 'add_account') {
        $rawMeta = trim((string) ($_POST['note'] ?? ''));
        $decoded = json_decode($rawMeta, true);
        $meta = is_array($decoded)
            ? $decoded
            : ['note' => $rawMeta];

        $accessToken = Crypto::encrypt(trim((string) ($_POST['access_token'] ?? '')));
        $refreshTokenRaw = trim((string) ($_POST['refresh_token'] ?? ''));
        $refreshToken = $refreshTokenRaw !== '' ? Crypto::encrypt($refreshTokenRaw) : null;
        $tokenExpiresAt = (int) ($_POST['token_expires_at'] ?? 0);
        $tokenExpiresAt = $tokenExpiresAt > 0 ? $tokenExpiresAt : null;

        $stmt = $pdo->prepare('INSERT INTO accounts(platform, account_name, account_id, access_token, refresh_token, token_expires_at, metadata_json) VALUES(:platform,:name,:account_id,:token,:refresh,:expires,:meta)');
        $stmt->execute([
            ':platform' => trim((string) ($_POST['platform'] ?? '')),
            ':name' => trim((string) ($_POST['account_name'] ?? '')),
            ':account_id' => trim((string) ($_POST['account_id'] ?? '')),
            ':token' => $accessToken,
            ':refresh' => $refreshToken,
            ':expires' => $tokenExpiresAt,
            ':meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
        ]);
        $logger->info('Account added', ['platform' => $_POST['platform'] ?? '', 'account_id' => $_POST['account_id'] ?? '']);
        redirect('/index.php?account_added=1');
    }

    if (($_POST['action'] ?? '') === 'run_diagnostic') {
        $diagService = new DiagnosticsService($pdo);
        $accountId = (int) ($_POST['diagnostic_account_id'] ?? 0);

        try {
            $_SESSION['diagnostic_report'] = $diagService->runForAccount($accountId);
            $logger->info('Diagnostic report generated', ['account_id' => $accountId]);
        } catch (Throwable $e) {
            $_SESSION['diagnostic_report'] = [
                'summary' => 'Диагностика завершилась ошибкой: ' . $e->getMessage(),
                'steps' => [[
                    'status' => 'fail',
                    'title' => 'Системная ошибка',
                    'message' => $e->getMessage(),
                ]],
            ];
            $logger->error('Diagnostic report failed', ['account_id' => $accountId, 'error' => $e->getMessage()]);
        }

        redirect('/index.php?diagnostic=1');
    }

}

$settings = $settingsRepo->all();
$accounts = $pdo->query('SELECT id, platform, account_name, account_id, token_expires_at, created_at FROM accounts ORDER BY id DESC')->fetchAll();
$comments = $pdo->query('SELECT c.*, a.account_name FROM comments c LEFT JOIN accounts a ON a.id = c.account_id ORDER BY c.id DESC LIMIT 50')->fetchAll();
$logs = $pdo->query('SELECT * FROM logs ORDER BY id DESC LIMIT 20')->fetchAll();

require dirname(__DIR__) . '/views/dashboard.php';
