<?php

require_once __DIR__ . '/../auth/SessionManager.php';

class AdminMiddleware
{
    public static function handle()
    {
        SessionManager::start();

        $user = SessionManager::get('user');

        if (
            !$user ||
            ($user['role'] ?? null) !== 'admin'
        ) {

            http_response_code(403);

            echo 'Forbidden';

            exit;
        }
    }
}
