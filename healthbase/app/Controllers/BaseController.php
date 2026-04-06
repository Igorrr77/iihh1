<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Router;

abstract class BaseController
{
    public function __construct(protected Container $container, protected Router $router)
    {
    }

    protected function db(): \PDO
    {
        return $this->container->get('db')->pdo();
    }
}
