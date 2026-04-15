<?php

namespace App\Core;

class Router
{
    private static array $routes = [];
    private static string $groupPrefix = '';
    private static array $groupMiddleware = [];
    private static array $currentMiddleware = [];

    public static function get(string $path, array|callable $handler, array $middleware = []): void
    {
        static::addRoute('GET', $path, $handler, $middleware);
    }

    public static function post(string $path, array|callable $handler, array $middleware = []): void
    {
        static::addRoute('POST', $path, $handler, $middleware);
    }

    public static function any(string $path, array|callable $handler, array $middleware = []): void
    {
        static::addRoute('GET', $path, $handler, $middleware);
        static::addRoute('POST', $path, $handler, $middleware);
    }

    public static function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousPrefix     = static::$groupPrefix;
        $previousMiddleware = static::$groupMiddleware;

        static::$groupPrefix     = $previousPrefix . $prefix;
        static::$groupMiddleware = array_merge($previousMiddleware, $middleware);

        $callback();

        static::$groupPrefix     = $previousPrefix;
        static::$groupMiddleware = $previousMiddleware;
    }

    private static function addRoute(string $method, string $path, array|callable $handler, array $middleware = []): void
    {
        $fullPath       = static::$groupPrefix . $path;
        $allMiddleware  = array_merge(static::$groupMiddleware, $middleware);

        static::$routes[] = [
            'method'     => $method,
            'path'       => $fullPath,
            'handler'    => $handler,
            'middleware' => $allMiddleware,
        ];
    }

    public static function dispatch(Request $request): void
    {
        $method = $request->method();
        $path   = $request->path();

        // Normalize path - remove trailing slash except root
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        foreach (static::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = static::pathToRegex($route['path']);

            if (preg_match($pattern, $path, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    $mw = new $middlewareClass();
                    $mw->handle($request);
                }

                // Call handler
                static::callHandler($route['handler'], $params, $request);
                return;
            }
        }

        // No route matched
        Response::notFound();
    }

    private static function pathToRegex(string $path): string
    {
        // Escape slashes and dots
        $pattern = preg_quote($path, '#');

        // Replace escaped {param} with named capture groups
        $pattern = preg_replace('#\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}#', '(?P<$1>[^/]+)', $pattern);

        return '#^' . $pattern . '$#';
    }

    private static function callHandler(array|callable $handler, array $params, Request $request): void
    {
        if (is_callable($handler)) {
            call_user_func($handler, $request, ...array_values($params));
            return;
        }

        [$controllerClass, $method] = $handler;

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller not found: {$controllerClass}");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method {$method} not found in {$controllerClass}");
        }

        // Pass route parameters as method arguments
        $reflection = new \ReflectionMethod($controller, $method);
        $args = [];

        foreach ($reflection->getParameters() as $param) {
            $paramName = $param->getName();
            if ($paramName === 'request') {
                $args[] = $request;
            } elseif (isset($params[$paramName])) {
                $args[] = $params[$paramName];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        $controller->$method(...$args);
    }

    public static function getRoutes(): array
    {
        return static::$routes;
    }
}
