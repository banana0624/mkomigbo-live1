<?php

declare(strict_types=1);

require_once PRIVATE_PATH . '/controllers/BaseController.php';

class HomeController extends BaseController
{
    public function index(): void
    {
        $this->render('home', [
            'title' => 'Welcome to Mkomigbo'
        ]);
    }
}