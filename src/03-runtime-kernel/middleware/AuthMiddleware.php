<?php

require_once __DIR__ . '/../auth/SessionManager.php';

class AuthMiddleware
{
    public static function requireAuth(): void
    {
        SessionManager::start();

        $user = SessionManager::get('user');

        if (!$user) {

            header('Content-Type: application/json');

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();

        $user = SessionManager::get('user');

        if (($user['role'] ?? null) !== 'admin') {

            http_response_code(403);

            exit('Forbidden');
        }
    }

    public static function requireStaff(): void
    {
        self::requireAuth();

        $user = SessionManager::get('user');

        $allowed = ['admin', 'staff'];

        if (!in_array($user['role'] ?? '', $allowed, true)) {

            http_response_code(403);

            exit('Forbidden');
        }
    }

    public static function requireContributor(): void
    {
        self::requireAuth();

        $user = SessionManager::get('user');

        $allowed = ['admin', 'staff', 'contributor'];

        if (!in_array($user['role'] ?? '', $allowed, true)) {

            http_response_code(403);

            exit('Forbidden');
        }
    }

    public static function optionalAuth(): void
    {
        SessionManager::start();
    }
}