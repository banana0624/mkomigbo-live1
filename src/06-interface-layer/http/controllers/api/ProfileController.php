<?php

require_once __DIR__ . '/../../../../03-runtime-kernel/auth/SessionManager.php';

class ProfileController
{
    public function me()
    {
        AuthMiddleware::requireAuth();

        SessionManager::start();

        $user = SessionManager::get('user');

        header('Content-Type: application/json');

        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    }
}