<?php

require_once __DIR__ . '/../../../../04-domain-services/auth/AuthService.php';
require_once __DIR__ . '/../../../../03-runtime-kernel/auth/SessionManager.php';
require_once __DIR__ . '/../../../../03-runtime-kernel/security/Csrf.php';

class WebLoginController
{
    public function login()
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {

            die('Invalid CSRF token');
        }
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $authService = new AuthService();

        $result = $authService->login($email, $password);

        if (!$result['success']) {

            echo 'Login failed';

            return;
        }

        SessionManager::start();

        SessionManager::set('user', $result['user']);

        header('Location: /public/index.php/dashboard');

        exit;
    }
}