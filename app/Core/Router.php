<?php

class Router
{
    public static function dispatch(string $uri): void
    {
        $path = trim($uri, '/');

        // Normalize
        if ($path === '' || $path === 'index.php') {
            require __DIR__ . '/../../public/home.php';
            return;
        }

        // Split segments
        $segments = explode('/', $path);
        $root = $segments[0];

        /*
        |--------------------------------------------------------------------------
        | ADMIN ROUTE
        |--------------------------------------------------------------------------
        */
        if ($root === 'admin') {
            require_once __DIR__ . '/../Controllers/AdminController.php';
            (new AdminController())->handle($segments);
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | MODULE ROUTES
        |--------------------------------------------------------------------------
        */
        $modules = ['subjects', 'contributors', 'platforms', 'staff'];

        if (in_array($root, $modules, true)) {
            $moduleFile = dirname(__DIR__, 2) . "/public/{$root}/index.php";

            if (is_file($moduleFile)) {
                require $moduleFile;
                return;
            }

            http_response_code(404);
            echo "<h1>Module not found: {$root}</h1>";
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | DEFAULT PAGE (CMS)
        |--------------------------------------------------------------------------
        */
        require_once __DIR__ . '/../Controllers/PageController.php';
        (new PageController())->show($path);
    }
}