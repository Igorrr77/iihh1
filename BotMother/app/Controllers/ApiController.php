<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use App\Graph\GraphCompiler;
use App\Graph\GraphValidator;
use App\Repositories\BotRepository;
use App\Repositories\ChatRepository;
use App\Repositories\ContactRepository;
use App\Repositories\DealRepository;
use App\Repositories\FunnelRepository;
use App\Repositories\MarketplaceRepository;
use App\Repositories\TemplateRepository;
use App\Repositories\ProcessRepository;
use App\Repositories\ProcessTemplateRepository;
use App\Repositories\ProjectRepository;
use App\Services\AuthService;
use App\Services\BotService;
use App\Services\ChatService;
use App\Services\ProcessService;

final class ApiController
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(Request $request): Response
    {
        $path = trim($request->path(), '/');
        $method = $request->method();

        if ($path === 'api/auth/login' && $method === 'POST') {
            $auth = $this->container->get(AuthService::class)->login((string)$request->input('email', ''), (string)$request->input('password', ''), (int)$request->input('account_id', 0));
            return $auth ? Response::json(['status' => 'ok', 'auth' => $auth]) : Response::json(['error' => 'invalid_credentials'], 401);
        }
        if ($path === 'api/auth/logout' && $method === 'POST') {
            $this->container->get(AuthService::class)->logout();
            return Response::json(['status' => 'ok']);
        }

        $scope = $this->requireScope();

        if ($path === 'api/projects' && $method === 'GET') return $this->projectsIndex($scope);
        if ($path === 'api/projects' && $method === 'POST') return $this->projectsStore($request, $scope);
        if (preg_match('#^api/projects/(\d+)$#', $path, $m) && $method === 'GET') return $this->projectsShow((int)$m[1], $scope);
        if (preg_match('#^api/projects/(\d+)$#', $path, $m) && $method === 'PUT') return $this->projectsUpdate((int)$m[1], $request, $scope);

        if ($path === 'api/bots' && $method === 'POST') return $this->botsStore($request, $scope);
        if (preg_match('#^api/bots/(\d+)/(verify|set-webhook|delete-webhook)$#', $path, $m) && $method === 'POST') return $this->botsAction((int)$m[1], $m[2], $scope);

        if ($path === 'api/processes' && $method === 'GET') return $this->processesIndex($scope);
        if ($path === 'api/processes' && $method === 'POST') return $this->processesStore($request, $scope);
        if (preg_match('#^api/processes/(\d+)/versions$#', $path, $m) && $method === 'POST') return $this->versionsStore((int)$m[1], $request, $scope);
        if (preg_match('#^api/process-versions/(\d+)$#', $path, $m) && $method === 'PUT') return $this->versionsUpdate((int)$m[1], $request, $scope);
        if (preg_match('#^api/process-versions/(\d+)/validate$#', $path, $m) && $method === 'POST') return $this->versionsValidate((int)$m[1], $scope);
        if (preg_match('#^api/process-versions/(\d+)/publish$#', $path, $m) && $method === 'POST') return $this->versionsPublish((int)$m[1], $scope);

        if ($path === 'api/contacts' && $method === 'GET') return $this->contactsIndex($request, $scope);
        if (preg_match('#^api/contacts/(\d+)$#', $path, $m) && $method === 'GET') return $this->contactsShow((int)$m[1], $scope);
        if (preg_match('#^api/contacts/(\d+)$#', $path, $m) && $method === 'PUT') return $this->contactsUpdate((int)$m[1], $request, $scope);
        if (preg_match('#^api/contacts/(\d+)/tags$#', $path, $m) && $method === 'POST') return $this->contactsAddTag((int)$m[1], $request, $scope);
        if (preg_match('#^api/contacts/(\d+)/tags/(\d+)$#', $path, $m) && $method === 'DELETE') return $this->contactsRemoveTag((int)$m[1], (int)$m[2], $scope);

        if ($path === 'api/chats' && $method === 'GET') return $this->chatsIndex($scope);
        if (preg_match('#^api/chats/(\d+)/messages$#', $path, $m) && $method === 'GET') return $this->chatsMessages((int)$m[1], $scope);
        if (preg_match('#^api/chats/(\d+)/send-message$#', $path, $m) && $method === 'POST') return $this->chatsSend((int)$m[1], $request, $scope);
        if (preg_match('#^api/chats/(\d+)/mode$#', $path, $m) && $method === 'POST') return $this->chatsMode((int)$m[1], $request, $scope);

        if ($path === 'api/funnels' && $method === 'GET') return $this->funnelsIndex($request, $scope);
        if ($path === 'api/funnels' && $method === 'POST') return $this->funnelsStore($request, $scope);
        if (preg_match('#^api/funnels/(\d+)/analytics$#', $path, $m) && $method === 'GET') return $this->funnelsAnalytics((int)$m[1], $scope);

        if ($path === 'api/pipelines' && $method === 'GET') return $this->pipelinesIndex($request, $scope);
        if ($path === 'api/pipelines' && $method === 'POST') return $this->pipelinesStore($request, $scope);
        if ($path === 'api/deals' && $method === 'GET') return $this->dealsIndex($request, $scope);
        if ($path === 'api/deals' && $method === 'POST') return $this->dealsStore($request, $scope);
        if (preg_match('#^api/deals/(\d+)/move-stage$#', $path, $m) && $method === 'POST') return $this->dealsMoveStage((int)$m[1], $request, $scope);
        if (preg_match('#^api/deals/(\d+)/notes$#', $path, $m) && $method === 'POST') return $this->dealsAddNote((int)$m[1], $request, $scope);
        if (preg_match('#^api/deals/(\d+)/tasks$#', $path, $m) && $method === 'POST') return $this->dealsAddTask((int)$m[1], $request, $scope);

        if ($path === 'api/templates/message' && $method === 'GET') return $this->messageTemplatesIndex($scope);
        if ($path === 'api/templates/message' && $method === 'POST') return $this->messageTemplatesStore($request, $scope);
        if ($path === 'api/templates/reusable' && $method === 'GET') return $this->reusableBlocksIndex($scope);
        if ($path === 'api/templates/reusable' && $method === 'POST') return $this->reusableBlocksStore($request, $scope);
        if (preg_match('#^api/templates/message/(\d+)/export$#', $path, $m) && $method === 'GET') return $this->messageTemplateExport((int)$m[1], $scope);
        if ($path === 'api/templates/message/import' && $method === 'POST') return $this->messageTemplateImport($request, $scope);

        if ($path === 'api/marketplace/items' && $method === 'GET') return $this->marketplaceItems();
        if (preg_match('#^api/marketplace/items/(\d+)$#', $path, $m) && $method === 'GET') return $this->marketplaceItem((int)$m[1]);
        if (preg_match('#^api/marketplace/items/(\d+)/install$#', $path, $m) && $method === 'POST') return $this->marketplaceInstall((int)$m[1], $request, $scope);
        if (preg_match('#^api/marketplace/items/(\d+)/export$#', $path, $m) && $method === 'GET') return $this->marketplaceExport((int)$m[1], $scope);
        if ($path === 'api/marketplace/import' && $method === 'POST') return $this->marketplaceImport($request, $scope);

        if ($path === 'api/process-templates' && $method === 'GET') return $this->processTemplatesIndex($scope);
        if ($path === 'api/process-templates' && $method === 'POST') return $this->processTemplatesStore($request, $scope);

        return Response::json(['error' => 'not_found'], 404);
    }

    private function projectsIndex(?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        return Response::json(['data' => $this->container->get(ProjectRepository::class)->allByAccount($scope['account_id'])]);
    }

    private function projectsStore(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $project = $this->container->get(ProjectRepository::class)->create([
            'account_id' => $scope['account_id'], 'created_by' => $scope['user_id'], 'name' => $request->input('name', 'Untitled project'),
            'slug' => $request->input('slug', 'project-' . time()), 'description' => $request->input('description'), 'status' => $request->input('status', 'active'),
        ]);
        return Response::json(['data' => $project], 201);
    }

    private function projectsShow(int $id, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $project = $this->container->get(ProjectRepository::class)->findById($id, $scope['account_id']);
        return $project ? Response::json(['data' => $project]) : Response::json(['error' => 'not_found'], 404);
    }

    private function projectsUpdate(int $id, Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $updated = $this->container->get(ProjectRepository::class)->update($id, $scope['account_id'], $request->body());
        return $updated ? Response::json(['data' => $updated]) : Response::json(['error' => 'not_found'], 404);
    }

    private function botsStore(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $service = new BotService($this->container->get('telegramConfig'), $this->container->get(BotRepository::class));
        $bot = $service->create([
            'account_id' => $scope['account_id'], 'created_by' => $scope['user_id'], 'project_id' => (int)$request->input('project_id', 0),
            'name' => (string)$request->input('name', 'New Bot'), 'token' => (string)$request->input('token', ''),
        ]);
        return Response::json(['data' => $bot], 201);
    }

    private function botsAction(int $id, string $action, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $service = new BotService($this->container->get('telegramConfig'), $this->container->get(BotRepository::class));
        return Response::json($service->action($id, $action));
    }

    private function processesIndex(?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        return Response::json((new ProcessService($this->container))->list($scope));
    }

    private function processesStore(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        return Response::json((new ProcessService($this->container))->create($request->body(), $scope), 201);
    }

    private function versionsStore(int $processId, Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $repo = $this->container->get(ProcessRepository::class);
        $process = $repo->findProcess($processId, $scope['account_id']);
        if (!$process) return Response::json(['error' => 'process_not_found'], 404);
        $graph = $request->body()['graph_json'] ?? ['schema_version' => '1.0.0', 'nodes' => [], 'edges' => [], 'comments' => [], 'groups' => []];
        return Response::json(['data' => $repo->createVersion($processId, $scope['user_id'], $graph)], 201);
    }

    private function versionsUpdate(int $versionId, Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $version = $this->container->get(ProcessRepository::class)->updateVersion($versionId, $request->body()['graph_json'] ?? []);
        return $version ? Response::json(['data' => $version]) : Response::json(['error' => 'version_not_found'], 404);
    }

    private function versionsValidate(int $versionId, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $repo = $this->container->get(ProcessRepository::class);
        $version = $repo->findVersion($versionId);
        if (!$version) return Response::json(['error' => 'version_not_found'], 404);
        $graph = json_decode((string)$version['graph_json'], true) ?: [];
        $result = (new GraphValidator())->validate($graph);
        $repo->saveValidation($versionId, $result['status'], $result['errors'], $result['warnings']);
        return Response::json($result);
    }

    private function versionsPublish(int $versionId, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $repo = $this->container->get(ProcessRepository::class);
        $version = $repo->findVersion($versionId);
        if (!$version) return Response::json(['error' => 'version_not_found'], 404);

        $graph = json_decode((string)$version['graph_json'], true) ?: [];
        $validation = (new GraphValidator())->validate($graph);
        if ($validation['status'] !== 'valid') {
            $repo->saveValidation($versionId, 'invalid', $validation['errors'], $validation['warnings']);
            return Response::json(['error' => 'validation_failed', 'details' => $validation], 422);
        }

        $compiled = (new GraphCompiler())->compile($graph);
        $this->persistCompiledGraph($versionId, $compiled);
        return Response::json(['data' => $repo->publishVersion($versionId), 'compiled_hash' => $compiled['hash']]);
    }

    private function contactsIndex(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $repo = $this->container->get(ContactRepository::class);
        return Response::json(['data' => $repo->list($scope['account_id'], (int)$request->input('project_id', 0))]);
    }

    private function contactsShow(int $id, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $contact = $this->container->get(ContactRepository::class)->find($id, $scope['account_id']);
        return $contact ? Response::json(['data' => $contact]) : Response::json(['error' => 'not_found'], 404);
    }

    private function contactsUpdate(int $id, Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $contact = $this->container->get(ContactRepository::class)->update($id, $scope['account_id'], $request->body());
        return $contact ? Response::json(['data' => $contact]) : Response::json(['error' => 'not_found'], 404);
    }

    private function contactsAddTag(int $id, Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $projectId = (int)$request->input('project_id', 0);
        $tagCode = (string)$request->input('tag_code', 'tag');
        $this->container->get(ContactRepository::class)->addTag($id, $projectId, $tagCode, $scope['user_id']);
        return Response::json(['status' => 'ok']);
    }

    private function contactsRemoveTag(int $id, int $tagId, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $this->container->get(ContactRepository::class)->removeTag($id, $tagId);
        return Response::json(['status' => 'ok']);
    }

    private function chatsIndex(?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        return Response::json(['data' => $this->container->get(ChatRepository::class)->list($scope['account_id'])]);
    }

    private function chatsMessages(int $chatId, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        return Response::json(['data' => $this->container->get(ChatRepository::class)->messages($chatId)]);
    }

    private function chatsSend(int $chatId, Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $chat = $this->container->get(ChatRepository::class)->find($chatId, $scope['account_id']);
        if (!$chat) return Response::json(['error' => 'chat_not_found'], 404);
        $result = $this->container->get(ChatService::class)->sendMessage($chat, (string)$request->input('text', ''));
        return Response::json($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    private function chatsMode(int $chatId, Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $mode = (string)$request->input('mode', 'hybrid');
        if (!in_array($mode, ['auto', 'manual', 'hybrid'], true)) {
            return Response::json(['error' => 'invalid_mode'], 422);
        }
        $this->container->get(ChatRepository::class)->setMode($chatId, $mode);
        return Response::json(['status' => 'ok', 'mode' => $mode]);
    }

    private function funnelsIndex(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        return Response::json(['data' => $this->container->get(FunnelRepository::class)->list((int)$request->input('project_id', 0))]);
    }

    private function funnelsStore(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $funnel = $this->container->get(FunnelRepository::class)->create([
            'account_id' => $scope['account_id'],
            'project_id' => (int)$request->input('project_id', 0),
            'bot_id' => (int)$request->input('bot_id', 0) ?: null,
            'name' => (string)$request->input('name', 'New Funnel'),
            'slug' => (string)$request->input('slug', 'funnel-' . time()),
            'description' => $request->input('description'),
            'status' => (string)$request->input('status', 'draft'),
            'created_by' => $scope['user_id'],
        ]);
        return Response::json(['data' => $funnel], 201);
    }

    private function funnelsAnalytics(int $funnelId, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        return Response::json(['data' => $this->container->get(FunnelRepository::class)->analytics($funnelId)]);
    }

    private function pipelinesIndex(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        return Response::json(['data' => $this->container->get(DealRepository::class)->pipelines((int)$request->input('project_id', 0))]);
    }

    private function pipelinesStore(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $pipeline = $this->container->get(DealRepository::class)->createPipeline([
            'account_id' => $scope['account_id'],
            'project_id' => (int)$request->input('project_id', 0),
            'name' => (string)$request->input('name', 'Sales Pipeline'),
            'slug' => (string)$request->input('slug', 'pipeline-' . time()),
            'description' => $request->input('description'),
            'status' => (string)$request->input('status', 'active'),
            'created_by' => $scope['user_id'],
        ]);
        return Response::json(['data' => $pipeline], 201);
    }

    private function dealsIndex(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        return Response::json(['data' => $this->container->get(DealRepository::class)->deals((int)$request->input('project_id', 0))]);
    }

    private function dealsStore(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $deal = $this->container->get(DealRepository::class)->createDeal([
            'account_id' => $scope['account_id'],
            'project_id' => (int)$request->input('project_id', 0),
            'bot_id' => (int)$request->input('bot_id', 0) ?: null,
            'contact_id' => (int)$request->input('contact_id', 0),
            'pipeline_id' => (int)$request->input('pipeline_id', 0),
            'stage_id' => (int)$request->input('stage_id', 0),
            'title' => (string)$request->input('title', 'New Deal'),
            'amount' => $request->input('amount'),
            'currency' => (string)$request->input('currency', 'USD'),
            'status' => (string)$request->input('status', 'open'),
            'manager_id' => (int)$request->input('manager_id', 0) ?: null,
            'created_by' => $scope['user_id'],
        ]);
        return Response::json(['data' => $deal], 201);
    }

    private function dealsMoveStage(int $dealId, Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $deal = $this->container->get(DealRepository::class)->moveStage($dealId, (int)$request->input('stage_id', 0), $scope['user_id']);
        return $deal ? Response::json(['data' => $deal]) : Response::json(['error' => 'deal_not_found'], 404);
    }

    private function dealsAddNote(int $dealId, Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $this->container->get(DealRepository::class)->addNote($dealId, $scope['user_id'], (string)$request->input('note', ''));
        return Response::json(['status' => 'ok']);
    }

    private function dealsAddTask(int $dealId, Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $this->container->get(DealRepository::class)->addTask($dealId, $scope['user_id'], (string)$request->input('title', 'Follow up'), $request->input('due_at'));
        return Response::json(['status' => 'ok']);
    }

    private function messageTemplatesIndex(?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        return Response::json(['data' => $this->container->get(TemplateRepository::class)->messageTemplates($scope['account_id'])]);
    }

    private function messageTemplatesStore(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $template = $this->container->get(TemplateRepository::class)->createMessageTemplate([
            'account_id' => $scope['account_id'],
            'project_id' => (int)$request->input('project_id', 0) ?: null,
            'name' => (string)$request->input('name', 'Message template'),
            'slug' => (string)$request->input('slug', 'message-template-' . time()),
            'template_type' => (string)$request->input('template_type', 'text'),
            'description' => $request->input('description'),
            'status' => (string)$request->input('status', 'draft'),
            'created_by' => $scope['user_id'],
            'payload_json' => $request->input('payload_json', ['type' => 'text', 'text' => '']),
        ]);
        return Response::json(['data' => $template], 201);
    }

    private function reusableBlocksIndex(?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        return Response::json(['data' => $this->container->get(TemplateRepository::class)->reusableBlocks($scope['account_id'])]);
    }

    private function reusableBlocksStore(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $block = $this->container->get(TemplateRepository::class)->createReusableBlock([
            'account_id' => $scope['account_id'],
            'project_id' => (int)$request->input('project_id', 0) ?: null,
            'name' => (string)$request->input('name', 'Reusable block'),
            'slug' => (string)$request->input('slug', 'reusable-block-' . time()),
            'description' => $request->input('description'),
            'status' => (string)$request->input('status', 'draft'),
            'created_by' => $scope['user_id'],
            'graph_json' => $request->input('graph_json', ['nodes' => [], 'edges' => []]),
            'compiled_graph_json' => $request->input('compiled_graph_json'),
            'input_contract_json' => $request->input('input_contract_json'),
            'output_contract_json' => $request->input('output_contract_json'),
        ]);
        return Response::json(['data' => $block], 201);
    }


    private function messageTemplateExport(int $templateId, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $pkg = $this->container->get(TemplateRepository::class)->exportMessageTemplate($templateId);
        return $pkg ? Response::json(['data' => $pkg]) : Response::json(['error' => 'not_found'], 404);
    }

    private function messageTemplateImport(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $package = $request->input('package', []);
        if (!is_array($package)) {
            return Response::json(['error' => 'invalid_package'], 422);
        }
        $result = $this->container->get(TemplateRepository::class)->importMessageTemplate($scope['account_id'], $scope['user_id'], $package);
        return Response::json(['data' => $result], 201);
    }

    private function marketplaceItems(): Response
    {
        return Response::json(['data' => $this->container->get(MarketplaceRepository::class)->items()]);
    }

    private function marketplaceItem(int $itemId): Response
    {
        $item = $this->container->get(MarketplaceRepository::class)->find($itemId);
        return $item ? Response::json(['data' => $item]) : Response::json(['error' => 'not_found'], 404);
    }

    private function marketplaceInstall(int $itemId, Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $result = $this->container->get(MarketplaceRepository::class)->install($itemId, $scope['account_id'], (int)$request->input('project_id', 0) ?: null);
        return isset($result['error']) ? Response::json($result, 422) : Response::json($result, 201);
    }


    private function marketplaceExport(int $itemId, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $item = $this->container->get(MarketplaceRepository::class)->find($itemId);
        if (!$item) return Response::json(['error' => 'not_found'], 404);
        return Response::json(['data' => ['package_version' => '1.0.0', 'item' => $item]]);
    }

    private function marketplaceImport(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $pkg = $request->input('package', []);
        if (!is_array($pkg)) return Response::json(['error' => 'invalid_package'], 422);

        // foundation import: install by item id if present
        $itemId = (int)($pkg['item']['id'] ?? 0);
        if ($itemId <= 0) return Response::json(['error' => 'item_id_missing'], 422);

        $result = $this->container->get(MarketplaceRepository::class)->install($itemId, $scope['account_id'], (int)$request->input('project_id', 0) ?: null);
        return isset($result['error']) ? Response::json($result, 422) : Response::json($result, 201);
    }

    private function processTemplatesIndex(?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        return Response::json(['data' => $this->container->get(ProcessTemplateRepository::class)->list($scope['account_id'])]);
    }

    private function processTemplatesStore(Request $request, ?array $scope): Response
    {
        if (!$scope) return Response::json(['error' => 'unauthorized'], 401);
        $template = $this->container->get(ProcessTemplateRepository::class)->create([
            'account_id' => $scope['account_id'],
            'project_id' => (int)$request->input('project_id', 0) ?: null,
            'name' => (string)$request->input('name', 'Process template'),
            'slug' => (string)$request->input('slug', 'process-template-' . time()),
            'description' => $request->input('description'),
            'graph_json' => $request->input('graph_json', ['nodes' => [], 'edges' => []]),
            'compiled_graph_json' => $request->input('compiled_graph_json'),
            'meta_json' => $request->input('meta_json'),
            'status' => (string)$request->input('status', 'draft'),
            'created_by' => $scope['user_id'],
        ]);
        return Response::json(['data' => $template], 201);
    }

    private function persistCompiledGraph(int $versionId, array $compiled): void
    {
        $path = __DIR__ . '/../../storage/compiled_graphs/' . $versionId . '.json';
        file_put_contents($path, json_encode($compiled, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->container->get(ProcessRepository::class)->saveCompiled($versionId, $compiled);
    }

    private function requireScope(): ?array
    {
        $auth = $this->container->get(AuthService::class)->auth();
        if (!$auth) return null;
        return ['user_id' => (int)$auth['user_id'], 'account_id' => (int)$auth['account_id'], 'role_code' => (string)$auth['role_code']];
    }
}
