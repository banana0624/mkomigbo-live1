<?php

declare(strict_types=1);

require_once PRIVATE_PATH . '/assets/auth.php';

class AdminMiddleware
{
    public static function handle(): void
    {
        require_admin();
    }
}