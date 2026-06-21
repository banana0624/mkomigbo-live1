<?php

require_once __DIR__ . '/../../../../04-domain-services/auth/AuthService.php';
require_once __DIR__ . '/../../../../03-runtime-kernel/auth/SessionManager.php';

class AuthController
{
    public function login()
    {
        $input = json_decode(file_get_contents("php://input"), true);

        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        $authService = new AuthService();

        $result = $authService->login($email, $password);

        if ($result['success']) {

            $user = $result['user'];

            SessionManager::start();

            SessionManager::set('user', [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]);
        }

        header('Content-Type: application/json');

        echo json_encode($result);
    }
}