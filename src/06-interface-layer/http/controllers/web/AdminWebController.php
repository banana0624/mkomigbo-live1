<?php

require_once __DIR__ . '/../../../../03-runtime-kernel/auth/RoleMiddleware.php';
require_once __DIR__ . '/../../../../03-runtime-kernel/auth/Auth.php';
require_once __DIR__ . '/../../../../03-runtime-kernel/view/View.php';

class AdminWebController
{
    public function index()
    {
        RoleMiddleware::requireRole('admin');

        $user = Auth::user();

        $content = '

            <h1>Admin Panel</h1>

            <p>
                Authenticated administrator runtime
            </p>

            <ul>
                <li>
                    <strong>ID:</strong>
                    ' . htmlspecialchars($user['id']) . '
                </li>

                <li>
                    <strong>Email:</strong>
                    ' . htmlspecialchars($user['email']) . '
                </li>

                <li>
                    <strong>Role:</strong>
                    ' . htmlspecialchars($user['role']) . '
                </li>
            </ul>

        ';

        View::render('Admin Panel', $content);
    }
}