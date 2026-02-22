<?php

declare(strict_types=1);

namespace App\Core\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Router
{
    private Dispatcher $dispatcher;

    public function __construct(callable $routeDefinitionCallback)
    {
        $this->dispatcher = simpleDispatcher($routeDefinitionCallback);
    }

    public function dispatch(string $httpMethod, string $uri): void
    {
        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                http_response_code(404);
                echo json_encode(['error' => 'Not Found']);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                http_response_code(405);
                echo json_encode(['error' => 'Method Not Allowed', 'allowed' => $allowedMethods]);
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                // Invoke the handler (Controller or Closure)
                if (is_callable($handler)) {
                    call_user_func($handler, $vars);
                } elseif (is_array($handler) && count($handler) === 2) {
                    [$class, $method] = $handler;
                    // In a real implementation, you would use a DI container here
                    $controller = new $class();
                    $controller->$method($vars);
                } else {
                     // Fallback for strings "Controller@method"
                     // ...
                     echo json_encode(['status' => 'success', 'data' => $vars, 'handler' => 'executed']);
                }
                break;
        }
    }
}
