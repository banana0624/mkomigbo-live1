<?php

require_once __DIR__ . '/../../../../03-runtime-kernel/auth/RoleMiddleware.php';
require_once __DIR__ . '/../../../../03-runtime-kernel/auth/Auth.php';
require_once __DIR__ . '/../../../../03-runtime-kernel/middleware/AdminMiddleware.php';

class AdminController
{
    public function me()
    {
        RoleMiddleware::requireRole('admin');
        AdminMiddleware::handle();

        header('Content-Type: application/json');

        echo json_encode([
            'success' => true,
            'admin' => Auth::user()
        ]);
    }
}