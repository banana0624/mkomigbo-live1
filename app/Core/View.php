<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(
        string $view,
        array $data = []
    ): void {

        extract($data);

        $viewFile =
            APP_ROOT .
            '/app/Views/' .
            $view .
            '.php';

        if (! file_exists($viewFile)) {

            http_response_code(500);

            echo "VIEW NOT FOUND: {$view}";

            exit;
        }

        require $viewFile;
    }
}