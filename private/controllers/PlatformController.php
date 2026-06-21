<?php

declare(strict_types=1);

require_once PRIVATE_PATH . '/controllers/BaseController.php';

class PlatformController extends BaseController
{
    public function index(): void
    {
        $this->render(
            'platforms/index',
            [
                'title' => 'Platforms'
            ]
        );
    }
}