<?php

namespace App\Support;

class Router
{
    private static array $routes = [];

    public static function get(string $path, $handler, array $middlewares = []): void
    {
        self::addRoute('GET', $path, $handler, $middlewares);
    }

    public static function post(string $path, $handler, array $middlewares = []): void
    {
        self::addRoute('POST', $path, $handler, $middlewares);
    }

    public static function put(string $path, $handler, array $middlewares = []): void
    {
        self::addRoute('PUT', $path, $handler, $middlewares);
    }

    public static function delete(string $path, $handler, array $middlewares = []): void
    {
        self::addRoute('DELETE', $path, $handler, $middlewares);
    }

    private static function addRoute(string $method, string $path, $handler, array $middlewares): void
    {
        $path = '/' . trim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        self::$routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public static function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method && !($method === 'HEAD' && $route['method'] === 'GET')) {
                continue;
            }

            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                // Run middlewares
                foreach ($route['middlewares'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    $result = $middleware->handle($request);
                    if ($result instanceof Response) {
                        return $result;
                    }
                }

                // Execute handler
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    $result = $controller->$action($request, ...array_values($params));
                } elseif (is_callable($handler)) {
                    $result = $handler($request, ...array_values($params));
                } else {
                    throw new \RuntimeException('Invalid route handler');
                }

                if ($result instanceof Response) {
                    return $result;
                }

                return new Response((string)$result);
            }
        }

        return new Response(
            View::render('errors.404', ['title' => 'Page Not Found'], 'auth'),
            404
        );
    }
}
