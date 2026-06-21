<?php

declare(strict_types=1);

require_once PRIVATE_PATH . '/controllers/BaseController.php';

class CalendarController extends BaseController
{
    public function index(): void
    {
        $this->render(
            'calendar/index',
            [
                'title' => 'Igbo Calendar'
            ]
        );
    }
}