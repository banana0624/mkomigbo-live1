<?php

declare(strict_types=1);

final class SubjectController
{
    public function index(): void
    {
        require PRIVATE_PATH . '/views/subjects/index.php';
    }
}