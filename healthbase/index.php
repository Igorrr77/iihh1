<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Middleware\AdminMiddleware;

require_once __DIR__ . '/app/bootstrap.php';

if (!is_file(root_path('.env')) || !is_file(root_path('storage/install.lock'))) {
    header('Location: ' . url('/install/index.php'));
    exit;
}

$container = new Container();
$container->set('db', fn() => new Database(config('database')));
$container->set('middleware.admin', fn() => new AdminMiddleware());

$router = new Router();
(require root_path('config/routes.php'))($router);
$router->dispatch(new Request(), $container);
