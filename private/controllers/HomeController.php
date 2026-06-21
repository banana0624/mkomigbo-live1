<?php

declare(strict_types=1);

class HomeController
{
    public function index(): void
    {
        $title = 'Home';

        $view = PRIVATE_PATH . '/views/home/index.php';

        require PRIVATE_PATH . '/views/layouts/main.php';
    }
}