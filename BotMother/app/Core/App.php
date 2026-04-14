<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\ApiController;
use App\Controllers\WebhookController;
use App\Repositories\BotRepository;
use App\Repositories\ChatRepository;
use App\Repositories\ContactRepository;
use App\Repositories\DealRepository;
use App\Repositories\ExecutionRepository;
use App\Repositories\FunnelRepository;
use App\Repositories\GuardrailRepository;
use App\Repositories\MarketplaceRepository;
use App\Repositories\TemplateRepository;
use App\Repositories\ProcessRepository;
use App\Repositories\ProcessTemplateRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\ChatService;
use App\Services\RuntimeEngineService;
use App\Services\RuntimeService;

final class App
{
    private Container $container;

    public function __construct(array $config, array $db, array $security, array $queue, array $telegram)
    {
        $this->container = new Container();
        $this->container->set('config', fn () => $config);
        $this->container->set('dbConfig', fn () => $db);
        $this->container->set('security', fn () => $security);
        $this->container->set('queue', fn () => $queue);
        $this->container->set('telegramConfig', fn () => $telegram);

        $this->container->set(Logger::class, fn () => new Logger(__DIR__ . '/../../storage/logs/app.log'));
        $this->container->set(Database::class, fn (Container $c) => new Database($c->get('dbConfig')));
        $this->container->set(Session::class, fn (Container $c) => tap(new Session(), fn (Session $s) => $s->start($c->get('security')['session_name'])));
        $this->container->set(Csrf::class, fn (Container $c) => new Csrf($c->get(Session::class), $c->get('security')['csrf_token_name']));

        $this->container->set(UserRepository::class, fn (Container $c) => new UserRepository($c->get(Database::class)));
        $this->container->set(ProjectRepository::class, fn (Container $c) => new ProjectRepository($c->get(Database::class)));
        $this->container->set(BotRepository::class, fn (Container $c) => new BotRepository($c->get(Database::class)));
        $this->container->set(ProcessRepository::class, fn (Container $c) => new ProcessRepository($c->get(Database::class)));
        $this->container->set(ContactRepository::class, fn (Container $c) => new ContactRepository($c->get(Database::class)));
        $this->container->set(ChatRepository::class, fn (Container $c) => new ChatRepository($c->get(Database::class)));
        $this->container->set(FunnelRepository::class, fn (Container $c) => new FunnelRepository($c->get(Database::class)));
        $this->container->set(DealRepository::class, fn (Container $c) => new DealRepository($c->get(Database::class)));
        $this->container->set(TemplateRepository::class, fn (Container $c) => new TemplateRepository($c->get(Database::class)));
        $this->container->set(MarketplaceRepository::class, fn (Container $c) => new MarketplaceRepository($c->get(Database::class)));
        $this->container->set(ExecutionRepository::class, fn (Container $c) => new ExecutionRepository($c->get(Database::class)));
        $this->container->set(ProcessTemplateRepository::class, fn (Container $c) => new ProcessTemplateRepository($c->get(Database::class)));
        $this->container->set(GuardrailRepository::class, fn (Container $c) => new GuardrailRepository($c->get(Database::class)));

        $this->container->set(AuthService::class, fn (Container $c) => new AuthService($c->get(UserRepository::class), $c->get(Session::class)));
        $this->container->set(RuntimeService::class, fn (Container $c) => new RuntimeService($c->get(Database::class), $c->get(Logger::class), $c->get(ExecutionRepository::class), new RuntimeEngineService($c->get(ExecutionRepository::class))));
        $this->container->set(ChatService::class, fn (Container $c) => new ChatService($c->get(ChatRepository::class), $c->get(BotRepository::class)));
    }

    public function handleHttp(): void
    {
        $request = Request::fromGlobals();
        if ($request->path() === '/') {
            $this->render(__DIR__ . '/../../views/pages/dashboard.php');
            return;
        }

        if (str_starts_with($request->path(), '/editor')) {
            $this->render(__DIR__ . '/../../views/pages/editor.php');
            return;
        }

        Response::json(['status' => 'ok', 'message' => 'BotMother foundation running'])->send();
    }

    public function handleApi(): void
    {
        (new ApiController($this->container))->handle(Request::fromGlobals())->send();
    }

    public function handleWebhook(): void
    {
        (new WebhookController($this->container))->handle(Request::fromGlobals())->send();
    }

    private function render(string $file): void
    {
        ob_start();
        include $file;
        Response::html((string)ob_get_clean())->send();
    }
}

function tap(mixed $value, callable $callback): mixed
{
    $callback($value);
    return $value;
}
