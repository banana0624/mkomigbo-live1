<?php

declare(strict_types=1);

abstract class BaseController
{
    protected function render(string $view, array $data = []): void
    {
        extract($data);

        $file = PRIVATE_PATH . '/views/' . $view . '.php';

        if (!file_exists($file)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view);
            exit;
        }

        require $file;
    }
}