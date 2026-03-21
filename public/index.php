<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\AnalysisService;
use App\Auth;
use App\GeminiClient;
use App\TelegramService;

$auth = new Auth($db);
$gemini = new GeminiClient($config['gemini']['api_key'], $config['gemini']['model']);
$analysisService = new AnalysisService($db, $gemini);
$telegram = new TelegramService($db, $analysisService);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

if ($uri === '/api/login' && $method === 'POST') {
    $body = json_decode((string) file_get_contents('php://input'), true) ?? [];
    $ok = $auth->login((string) ($body['email'] ?? ''), (string) ($body['password'] ?? ''));
    header('Content-Type: application/json');
    echo json_encode(['ok' => $ok]);
    exit;
}

if ($uri === '/api/logout' && $method === 'POST') {
    $auth->logout();
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

if ($uri === '/api/live-feed' && $method === 'GET') {
    $auth->requireAuth();
    $tenantId = (int) $auth->tenantId();
    $limit = max(1, min(100, (int) ($_GET['limit'] ?? 30)));
    $rows = $db->query(
        "SELECT a.id, a.sentiment, a.confidence_level, a.lead_score, a.churn_risk, a.objections_json, a.pain_points_json, a.personality_json, a.coaching_json, a.patterns_json, a.created_at, c.client_handle
         FROM analyses a
         JOIN conversations c ON c.id = a.conversation_id
         WHERE a.tenant_id = :tenant_id
         ORDER BY a.id DESC
         LIMIT {$limit}",
        ['tenant_id' => $tenantId]
    )->fetchAll();

    header('Content-Type: application/json');
    echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($uri === '/api/product-context' && $method === 'POST') {
    $auth->requireAuth();
    $body = json_decode((string) file_get_contents('php://input'), true) ?? [];
    $productText = (string) ($body['product_text'] ?? '');
    $db->query('INSERT INTO tenant_product_context (tenant_id, product_text, created_at) VALUES (:tenant_id, :product_text, UTC_TIMESTAMP())', [
        'tenant_id' => (int) $auth->tenantId(),
        'product_text' => $productText,
    ]);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

if ($uri === '/api/export' && $method === 'GET') {
    $auth->requireAuth();
    $format = $_GET['format'] ?? 'json';
    $tenantId = (int) $auth->tenantId();

    $rows = $db->query('SELECT * FROM analyses WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT 500', ['tenant_id' => $tenantId])->fetchAll();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="analysis_export.csv"');
        $out = fopen('php://output', 'wb');
        if ($out && isset($rows[0])) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
        }
        if ($out) {
            fclose($out);
        }
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($uri === '/api/integrations' && $method === 'POST') {
    $auth->requireAuth();
    $body = json_decode((string) file_get_contents('php://input'), true) ?? [];
    $crm = (string) ($body['crm_endpoint'] ?? '');
    $webhook = (string) ($body['webhook_url'] ?? '');
    $db->query(
        'INSERT INTO tenant_integrations (tenant_id, crm_endpoint, webhook_url, created_at) VALUES (:tenant_id, :crm_endpoint, :webhook_url, UTC_TIMESTAMP())',
        [
            'tenant_id' => (int) $auth->tenantId(),
            'crm_endpoint' => $crm !== '' ? $crm : null,
            'webhook_url' => $webhook !== '' ? $webhook : null,
        ]
    );
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

if ($uri === '/api/delete-request' && $method === 'POST') {
    $auth->requireAuth();
    $body = json_decode((string) file_get_contents('php://input'), true) ?? [];
    $conversationId = (int) ($body['conversation_id'] ?? 0);
    if ($conversationId > 0) {
        $db->query('DELETE FROM transcripts WHERE tenant_id = :tenant_id AND conversation_id = :conversation_id', ['tenant_id' => (int) $auth->tenantId(), 'conversation_id' => $conversationId]);
        $db->query('DELETE FROM analyses WHERE tenant_id = :tenant_id AND conversation_id = :conversation_id', ['tenant_id' => (int) $auth->tenantId(), 'conversation_id' => $conversationId]);
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

if (preg_match('#^/webhook/telegram/([a-z0-9\-_]+)/([A-Za-z0-9\-_]+)$#', $uri, $m) && $method === 'POST') {
    $payload = json_decode((string) file_get_contents('php://input'), true) ?? [];
    $telegram->handleWebhook($m[1], $m[2], $payload, $config['security']['webhook_secret']);
    exit;
}

if ($uri === '/' || $uri === '/admin') {
    require __DIR__ . '/views/admin.php';
    exit;
}

http_response_code(404);
echo 'Not found';
