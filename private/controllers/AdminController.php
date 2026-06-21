<?php

declare(strict_types=1);

class AdminController
{
    public function index(): void
    {
        $title = 'Admin Panel';

        $view = PRIVATE_PATH . '/views/admin/index.php';

        require PRIVATE_PATH . '/views/layouts/main.php';
    }
}