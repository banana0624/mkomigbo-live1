<?php
declare(strict_types=1);

if (!function_exists('mk_staff_flash_set')) {
    function mk_staff_flash_set(string $type, string $message): void
    {
        if (function_exists('mk__session_start')) {
            mk__session_start();
        } elseif (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $_SESSION['mk_staff_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}

if (!function_exists('mk_staff_flash_get')) {
    function mk_staff_flash_get(): ?array
    {
        if (function_exists('mk__session_start')) {
            mk__session_start();
        } elseif (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (
            empty($_SESSION['mk_staff_flash']) ||
            !is_array($_SESSION['mk_staff_flash'])
        ) {
            return null;
        }

        $flash = $_SESSION['mk_staff_flash'];
        unset($_SESSION['mk_staff_flash']);

        $type = isset($flash['type']) ? (string)$flash['type'] : 'info';
        $message = isset($flash['message']) ? trim((string)$flash['message']) : '';

        if ($message === '') {
            return null;
        }

        return [
            'type' => $type,
            'message' => $message,
        ];
    }
}