<?php

declare(strict_types=1);

class AdminMiddleware
{
    public static function handle(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user'])) {
            http_response_code(401);
            echo 'Unauthorized';
            exit;
        }

        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }
}