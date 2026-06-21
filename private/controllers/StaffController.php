<?php

declare(strict_types=1);

class StaffController
{
    public function index(): void
    {
        require PRIVATE_PATH . '/views/staff/dashboard.php';
    }

    public function tools(): void
    {
        echo "Staff Tools Page";
    }

    public function contributors(): void
    {
        echo "Staff Contributors Page";
    }

    public function platforms(): void
    {
        echo "Staff Platforms Page";
    }

    public function pages(): void
    {
        echo "Staff Pages Page";
    }
}