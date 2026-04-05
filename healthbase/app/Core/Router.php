<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $named = [];

    public function get(string $path, array $handler, string $name, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $name, $middleware);
    }

    public function post(string $path, array $handler, string $name, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $name, $middleware);
    }

    private function add(string $method, string $path, array $handler, string $name, array $middleware): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . rtrim($pattern, '/') . '$#';
        if ($path === '/') {
            $pattern = '#^/$#';
        }
        $this->routes[] = compact('method', 'path', 'pattern', 'handler', 'name', 'middleware');
        $this->named[$name] = $path;
    }

    public function url(string $name, array $params = []): string
    {
        $path = $this->named[$name] ?? '/';
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', (string)$value, $path);
        }
        return $path;
    }

    public function dispatch(Request $request, Container $container): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (!preg_match($route['pattern'], $request->uri, $matches)) {
                continue;
            }
            foreach ($route['middleware'] as $mw) {
                $middleware = $container->get('middleware.' . $mw);
                $middleware->handle($request);
            }
            [$class, $method] = $route['handler'];
            $controller = new $class($container, $this);
            $params = array_filter($matches, static fn($k) => is_string($k), ARRAY_FILTER_USE_KEY);
            $controller->$method($request, $params);
            return;
        }

        \App\Core\Response::view('errors/404', [], 404);
    }
}
