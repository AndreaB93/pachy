<?php
declare(strict_types=1);

namespace Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = [$method, $path, $handler];
    }

    public function get(string $path, array $handler): void    { $this->add('GET', $path, $handler); }
    public function post(string $path, array $handler): void   { $this->add('POST', $path, $handler); }
    public function put(string $path, array $handler): void    { $this->add('PUT', $path, $handler); }
    public function delete(string $path, array $handler): void { $this->add('DELETE', $path, $handler); }
    public function patch(string $path, array $handler): void  { $this->add('PATCH', $path, $handler); }

    public function dispatch(string $method, string $rawUri): void
    {
        // Strip query string from URI for routing
        $path = parse_url($rawUri, PHP_URL_PATH) ?: '/';

        $routes     = $this->routes;
        $dispatcher = simpleDispatcher(function(RouteCollector $r) use ($routes) {
            foreach ($routes as [$m, $p, $h]) {
                $r->addRoute($m, $p, $h);
            }
        });

        $result = $dispatcher->dispatch($method, $path);

        match($result[0]) {
            Dispatcher::FOUND => $this->handle($result[1], $result[2]),
            Dispatcher::NOT_FOUND => (function() {
                http_response_code(404);
                Response::view('errors/404');
            })(),
            Dispatcher::METHOD_NOT_ALLOWED => (function() {
                http_response_code(405);
                Response::json(['error' => 'Method Not Allowed']);
            })(),
        };
    }

    private function handle(array $handler, array $routeParams): void
    {
        $request = new Request();
        $request->setParams($routeParams);

        // Handler format: [Middleware1::class, Middleware2::class, [Controller::class, 'method']]
        $controllerDef = array_pop($handler);
        $middlewares   = $handler;

        foreach ($middlewares as $middleware) {
            (new $middleware)->handle($request);
        }

        [$controllerClass, $method] = $controllerDef;
        (new $controllerClass)->$method($request);
    }
}
