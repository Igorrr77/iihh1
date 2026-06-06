<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use Commentor\Database;
use Commentor\Env;
use Commentor\Logger;
use Commentor\WebhookNormalizer;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode'], $_GET['hub_verify_token'], $_GET['hub_challenge'])) {
    if ((string) $_GET['hub_verify_token'] === Env::get('WEBHOOK_VERIFY_TOKEN', '')) {
        header('Content-Type: text/plain; charset=utf-8');
        echo (string) $_GET['hub_challenge'];
        exit;
    }

    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$raw = (string) file_get_contents('php://input');
if (!verify_webhook_auth($raw)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$events = WebhookNormalizer::normalize($data);
if ($events === []) {
    http_response_code(202);
    echo json_encode(['ok' => true, 'accepted' => 0]);
    exit;
}

$pdo = Database::connection();
$logger = new Logger($pdo);
$accountByExternal = $pdo->prepare('SELECT id FROM accounts WHERE account_id = :account_id LIMIT 1');
$insert = $pdo->prepare('INSERT OR IGNORE INTO comments(platform, account_id, external_comment_id, external_media_id, commenter_handle, comment_text, content_context, status)
VALUES(:platform,:account_id,:external_comment_id,:external_media_id,:commenter_handle,:comment_text,:content_context,:status)');

$accepted = 0;
$duplicates = 0;
foreach ($events as $event) {
    if (empty($event['platform']) || empty($event['comment_text']) || empty($event['commenter_handle'])) {
        continue;
    }

    $accountByExternal->execute([':account_id' => (string) ($event['account_external_id'] ?? '')]);
    $account = $accountByExternal->fetch();
    if (!$account) {
        continue;
    }

    $insert->execute([
        ':platform' => (string) $event['platform'],
        ':account_id' => (int) $account['id'],
        ':external_comment_id' => (string) ($event['external_comment_id'] ?? ''),
        ':external_media_id' => (string) ($event['external_media_id'] ?? ''),
        ':commenter_handle' => ltrim((string) $event['commenter_handle'], '@'),
        ':comment_text' => (string) $event['comment_text'],
        ':content_context' => (string) ($event['content_context'] ?? ''),
        ':status' => 'pending',
    ]);

    if ($insert->rowCount() > 0) {
        $accepted++;
    } else {
        $duplicates++;
    }
}

$logger->info('Webhook batch ingested', ['accepted' => $accepted, 'duplicates' => $duplicates]);

echo json_encode(['ok' => true, 'accepted' => $accepted, 'duplicates' => $duplicates]);

function verify_webhook_auth(string $rawBody): bool
{
    $metaSignature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    if ($metaSignature !== '') {
        $appSecret = Env::get('META_APP_SECRET', '');
        if ($appSecret === '') {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
        return hash_equals($expected, $metaSignature);
    }

    $token = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    return $token !== '' && hash_equals(Env::get('WEBHOOK_SHARED_SECRET', ''), $token);
}
