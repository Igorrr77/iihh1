<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AceController;
use App\Controllers\AnalyticsController;
use App\Controllers\AnalyticsDeepController;
use App\Controllers\AuthController;
use App\Controllers\ChatController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\MarketingController;
use App\Controllers\OfferController;
use App\Controllers\PaymentController;
use App\Controllers\ReleaseController;
use App\Controllers\RoomController;
use App\Controllers\RuntimeController;
use App\Controllers\ScenarioController;
use App\Controllers\SchedulerController;
use App\Controllers\StreamController;
use App\Controllers\TimelineController;
use App\Controllers\WebinarController;

require_once __DIR__ . '/../app/bootstrap.php';

$router = new Router();
$router->get('/', [HomeController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);

$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->get('/api/auth/me', [AuthController::class, 'me']);
$router->post('/api/auth/refresh', [AuthController::class, 'refresh']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/auth/sessions', [AuthController::class, 'sessions']);
$router->post('/api/auth/revoke-all', [AuthController::class, 'revokeAll']);

$router->post('/api/webinars', [WebinarController::class, 'store']);
$router->post('/api/webinars/import-scenario', [WebinarController::class, 'importScenario']);
$router->get('/api/webinars/export-scenario', [WebinarController::class, 'exportScenario']);
$router->post('/api/webinars/generate-ai-scenario', [WebinarController::class, 'generateAiScenario']);

$router->post('/api/scenario/compile-macros', [ScenarioController::class, 'compileMacros']);
$router->post('/api/scenario/diff-versions', [ScenarioController::class, 'diffVersions']);
$router->post('/api/scenario/validate', [ScenarioController::class, 'validate']);
$router->post('/api/scenario/save-draft', [ScenarioController::class, 'saveDraft']);
$router->post('/api/scenario/publish', [ScenarioController::class, 'publish']);
$router->post('/api/scenario/rollback', [ScenarioController::class, 'rollback']);
$router->get('/api/scenario/versions', [ScenarioController::class, 'versions']);
$router->post('/api/scenario/preview', [ScenarioController::class, 'preview']);
$router->post('/api/scenario/import-adapter', [ScenarioController::class, 'importAdapter']);
$router->post('/api/scenario/export-adapter', [ScenarioController::class, 'exportAdapter']);

$router->post('/api/room/register', [RoomController::class, 'register']);
$router->post('/api/timeline/add-event', [TimelineController::class, 'addEvent']);
$router->post('/api/timeline/list-events', [TimelineController::class, 'listEvents']);
$router->post('/api/analytics/data-slice', [AnalyticsController::class, 'createDataSlice']);
$router->post('/api/analytics/utm-spend', [AnalyticsController::class, 'saveUtmSpend']);
$router->get('/api/analytics/attribution-report', [AnalyticsController::class, 'attributionReport']);
$router->post('/api/analytics/insight-ready', [AnalyticsController::class, 'recordInsightReady']);
$router->get('/api/analytics/insight-monitoring', [AnalyticsController::class, 'insightMonitoring']);
$router->post('/api/analytics/track-event', [AnalyticsDeepController::class, 'trackEvent']);
$router->get('/api/analytics/retention-heatmap', [AnalyticsDeepController::class, 'heatmap']);
$router->get('/api/analytics/export-csv', [AnalyticsDeepController::class, 'exportCsv']);

$router->post('/api/stream/resolve', [StreamController::class, 'resolvePlayback']);
$router->post('/api/stream/embed-token', [StreamController::class, 'createEmbedToken']);
$router->get('/api/stream/sdk-contract', [StreamController::class, 'sdkContract']);
$router->post('/api/stream/convert-live-to-auto', [StreamController::class, 'convertLiveToAuto']);
$router->post('/api/stream/room-state', [StreamController::class, 'setRoomState']);
$router->get('/api/stream/room-state', [StreamController::class, 'getRoomState']);

$router->post('/api/offers/create', [OfferController::class, 'create']);
$router->post('/api/offers/activate', [OfferController::class, 'activate']);
$router->get('/api/offers/active', [OfferController::class, 'active']);

$router->post('/api/payments/create-checkout', [PaymentController::class, 'createCheckout']);
$router->post('/api/payments/checkout-in-room', [PaymentController::class, 'checkoutInRoom']);
$router->post('/api/payments/webhook', [PaymentController::class, 'webhook']);
$router->get('/api/payments/reconciliation', [PaymentController::class, 'reconciliation']);
$router->get('/api/payments/psp-e2e-matrix', [PaymentController::class, 'pspE2eMatrix']);
$router->post('/api/payments/retry-checkout', [PaymentController::class, 'retryCheckout']);
$router->get('/api/payments/ops-dashboard', [PaymentController::class, 'opsDashboard']);

$router->post('/api/chat/send', [ChatController::class, 'send']);
$router->post('/api/chat/list', [ChatController::class, 'list']);
$router->get('/api/chat/stream', [ChatController::class, 'stream']);
$router->post('/api/chat/ask-ai', [ChatController::class, 'askAi']);
$router->post('/api/chat/moderate', [ChatController::class, 'moderate']);
$router->get('/api/chat/metrics', [ChatController::class, 'metrics']);

$router->post('/api/marketing/compute-segment', [MarketingController::class, 'computeSegment']);
$router->post('/api/marketing/enqueue-email', [MarketingController::class, 'enqueueEmailCadence']);
$router->post('/api/marketing/track-messenger-cuid', [MarketingController::class, 'trackMessengerCuid']);
$router->post('/api/marketing/route-crm', [MarketingController::class, 'routeCrm']);
$router->post('/api/marketing/process-channel-queue', [MarketingController::class, 'processChannelQueue']);
$router->post('/api/marketing/process-crm-queue', [MarketingController::class, 'processCrmQueue']);
$router->get('/api/marketing/dlq-summary', [MarketingController::class, 'dlqSummary']);
$router->get('/api/marketing/queue', [MarketingController::class, 'queue']);

$router->post('/api/ace/generate', [AceController::class, 'generate']);
$router->get('/api/ace/list', [AceController::class, 'list']);
$router->get('/api/ace/quality-benchmark', [AceController::class, 'qualityBenchmark']);

$router->get('/api/release/flags', [ReleaseController::class, 'listFlags']);
$router->post('/api/release/flags', [ReleaseController::class, 'setFlag']);
$router->post('/api/release/stage-status', [ReleaseController::class, 'setStageStatus']);
$router->get('/api/release/stages', [ReleaseController::class, 'listStages']);
$router->post('/api/release/sla', [ReleaseController::class, 'setSla']);
$router->get('/api/release/sla', [ReleaseController::class, 'listSla']);
$router->post('/api/release/incidents', [ReleaseController::class, 'addIncident']);
$router->post('/api/release/go-no-go', [ReleaseController::class, 'goNoGoReview']);
$router->get('/api/release/ga-gate-status', [ReleaseController::class, 'gaGateStatus']);
$router->get('/api/release/room-readiness', [ReleaseController::class, 'roomReadiness']);
$router->get('/api/release/policy-gate', [ReleaseController::class, 'policyGate']);
$router->post('/api/release/policy-gate', [ReleaseController::class, 'policyGate']);
$router->get('/api/release/ga-passport', [ReleaseController::class, 'gaPassport']);

$router->post('/api/scheduler/create-session', [SchedulerController::class, 'createSession']);
$router->get('/api/scheduler/next-session', [SchedulerController::class, 'nextSession']);
$router->get('/api/runtime/due-events', [RuntimeController::class, 'dueEvents']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
