<?php

declare(strict_types=1);

namespace InfraMind\Core;

use InfraMind\Middleware\Middleware;

/**
 * Simple router for handling HTTP requests.
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];

    /**
     * Register route.
     */
    public function register(
        string $method,
        string $path,
        string $controller,
        string $action,
        array $middlewares = [],
    ): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * Route a request.
     */
    public function route(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Match path with parameters
            $pattern = preg_replace('/{([^}]+)}/', '(?<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                // Extract parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                try {
                    // Apply middlewares
                    $response = $this->applyMiddlewares($request, $route['middlewares'], function ($req) use ($route, $params) {
                        return $this->executeController($req, $route['controller'], $route['action'], $params);
                    });

                    return $response;
                } catch (\Exception $e) {
                    return (new Response(500))->error('Internal server error: ' . $e->getMessage());
                }
            }
        }

        // Route not found
        return (new Response(404))->error('Not found');
    }

    /**
     * Apply middlewares in sequence.
     */
    private function applyMiddlewares(Request $request, array $middlewares, callable $next): Response
    {
        if (empty($middlewares)) {
            return $next($request);
        }

        $middleware = array_shift($middlewares);
        $instance = new $middleware();

        return $instance->handle($request, function ($req) use ($middlewares, $next) {
            return $this->applyMiddlewares($req, $middlewares, $next);
        });
    }

    /**
     * Execute controller action.
     */
    private function executeController(Request $request, string $controller, string $action, array $params): Response
    {
        $className = 'InfraMind\\Controllers\\' . $controller;

        if (!class_exists($className)) {
            throw new \RuntimeException("Controller not found: $className");
        }

        $instance = new $className();

        if (!method_exists($instance, $action)) {
            throw new \RuntimeException("Action not found: $className::$action");
        }

        // Call with request and params
        return $instance->$action($request, ...$params);
    }
}
