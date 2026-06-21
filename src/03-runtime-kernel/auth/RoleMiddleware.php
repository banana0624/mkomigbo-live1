<?php

require_once __DIR__ . '/Auth.php';

class RoleMiddleware
{
    public static function requireRole(string $role): void
    {
        if (!Auth::check()) {

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);

            exit;
        }

        if (Auth::role() !== $role) {

            http_response_code(403);

            echo json_encode([
                'success' => false,
                'message' => 'Forbidden'
            ]);

            exit;
        }
    }
}