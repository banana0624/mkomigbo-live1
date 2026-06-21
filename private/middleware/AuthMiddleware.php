<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * AUTH CORE
 */
require_once PRIVATE_PATH . '/auth/RBAC.php';
require_once PRIVATE_PATH . '/auth/Gate.php';

final class AuthMiddleware
{
    public static function requireAuth(): void
    {
        if (empty($_SESSION['staff_user_id'])) {
            self::unauthorized();
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();

        if (!Gate::allows('view_admin_panel')) {
            self::forbidden();
        }
    }

    public static function requireStaff(): void
    {
        self::requireAuth();

        if (!Gate::allows('access_staff')) {
            self::forbidden();
        }
    }

    public static function requireContributor(): void
    {
        self::requireAuth();

        if (!Gate::allows('access_contributor_area')) {
            self::forbidden();
        }
    }

    public static function optionalAuth(): void
    {
        // personalization layer later
    }

    private static function unauthorized(): never
    {
        header('Location: /staff/login.php');
        exit;
    }

    private static function forbidden(): never
    {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}