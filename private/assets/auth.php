<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function current_user_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

function require_admin(): void
{
    if (!is_logged_in()) {
        http_response_code(401);
        exit('Unauthorized');
    }

    if (current_user_role() !== 'admin') {
        http_response_code(403);
        exit('Forbidden');
    }
}