<?php

require_once __DIR__ . '/../../06-interface-layer/http/controllers/api/AuthController.php';
require_once __DIR__ . '/../../06-interface-layer/http/controllers/api/ProfileController.php';
require_once __DIR__ . '/../../06-interface-layer/http/controllers/api/LogoutController.php';
require_once __DIR__ . '/../../06-interface-layer/http/controllers/api/AdminController.php';
require_once __DIR__ . '/../../06-interface-layer/http/controllers/web/DashboardController.php';
require_once __DIR__ . '/../../06-interface-layer/http/controllers/web/AdminWebController.php';
require_once __DIR__ . '/../../06-interface-layer/http/controllers/web/LoginPageController.php';
require_once __DIR__ . '/../../06-interface-layer/http/controllers/web/WebLoginController.php';

class Router
{
    public function handle($uri)
    {
        $uri = str_replace('/public/index.php', '', $uri);

        if ($uri === '/api/login') {

            $controller = new AuthController();
            $controller->login();
            return;
        }

        if ($uri === '/api/me') {

            $controller = new ProfileController();
            $controller->me();
            return;
        }

        if ($uri === '/api/logout') {

            $controller = new LogoutController();
            $controller->logout();
            return;
        }
        
        if ($uri === '/api/admin/me') {

            $controller = new AdminController();
            $controller->me();
            return;
        }
        
        if ($uri === '/dashboard') {

            $controller = new DashboardController();
        
            $controller->index();
        
            return;
        }
        
        if ($uri === '/admin') {

            $controller = new AdminWebController();
        
            $controller->index();
        
            return;
        }
        
        if ($uri === '/login') {

            $controller = new LoginPageController();
        
            $controller->index();
        
            return;
        }
        
        if ($uri === '/web-login') {

            $controller = new WebLoginController();
        
            $controller->login();
        
            return;
        }

        header('Content-Type: application/json');

        echo json_encode([
            'message' => 'Route not found',
            'debug_uri' => $uri
        ]);
    }
}