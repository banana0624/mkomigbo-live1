<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public string $method;

    public string $path;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $path = parse_url($uri, PHP_URL_PATH);

        $path = trim((string)($path ?? '/'), '/');

        /*
        |--------------------------------------------------------------------------
        | Remove index.php prefix
        |--------------------------------------------------------------------------
        */

        if (str_starts_with($path, 'index.php/')) {

            $path = substr($path, 10);
        }

        if ($path === 'index.php') {

            $path = '';
        }

        $this->path = $path;
    }
}