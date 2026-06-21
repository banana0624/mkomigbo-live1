<?php

declare(strict_types=1);

class ControllerLoader
{
    public static function load(string $class): void
    {
        $path = PRIVATE_PATH . '/controllers/' . $class . '.php';

        if (!is_file($path)) {
            http_response_code(500);
            exit("Controller missing: {$class}");
        }

        require_once $path;
    }
}