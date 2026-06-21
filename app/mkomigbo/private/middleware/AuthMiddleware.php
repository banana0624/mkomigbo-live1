<?php

declare(strict_types=1);

class AuthMiddleware
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
    }
}