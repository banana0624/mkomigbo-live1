<?php

declare(strict_types=1);

final class RouteDispatcher
{
    public static function dispatch(string $uri): void
    {
        $uri = rtrim($uri, '/') ?: '/';

        foreach (Route::all() as $route) {

            if ($route['path'] !== $uri) {
                continue;
            }

            self::runMiddleware($route['middleware']);

            $controller = new $route['controller']();
            $method = $route['method'];

            $controller->$method();

            return;
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    private static function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $m) {

            match ($m) {

                'auth' => AuthMiddleware::requireAuth(),
                'admin' => AuthMiddleware::requireAdmin(),
                'staff' => AuthMiddleware::requireStaff(),
                'contributor' => AuthMiddleware::requireContributor(),
                'optional_auth' => AuthMiddleware::optionalAuth(),

                default => null
            };
        }
    }
}