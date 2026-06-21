<?php

require_once __DIR__ . '/../../../../03-runtime-kernel/auth/Auth.php';
require_once __DIR__ . '/../../../../03-runtime-kernel/view/View.php';

class DashboardController
{
    public function index()
    {
        AuthMiddleware::requireAuth();

        $user = Auth::user();

        $content = '

            <h1>Dashboard</h1>

            <p>
                Welcome, ' . htmlspecialchars($user['email']) . '
            </p>

            <p>
                Role: ' . htmlspecialchars($user['role']) . '
            </p>

        ';

        View::render('Dashboard', $content);
    }
}