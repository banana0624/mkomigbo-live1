<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!empty($_SESSION['flash'])) {
    echo '<div class="flash flash-success">' . htmlspecialchars($_SESSION['flash']) . '</div>';
    unset($_SESSION['flash']);
}

if (!empty($_SESSION['error'])) {
    echo '<div class="flash flash-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

function isLoggedIn(): bool {
    return isset($_SESSION['admin_user_id']);
}

function requireLogin(): void
    {
        if (empty($_SESSION['admin_user_id'])) {
    
            // Detect AJAX request
            $isAjax = (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            );
    
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized'
                ]);
                exit;
            }
    
            header('Location: /public/admin/login.php');
            exit;
        }
    }

function requireRole(string $role): void
{
    requireLogin();

    if (($_SESSION['admin_role'] ?? '') !== $role) {

        $isAjax = (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Forbidden'
            ]);
            exit;
        }

        die('Access denied');
    }
}