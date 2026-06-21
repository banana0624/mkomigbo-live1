<?php

require_once __DIR__ . '/../auth/SessionManager.php';

class Csrf
{
    public static function token(): string
    {
        SessionManager::start();

        if (!SessionManager::get('csrf_token')) {

            SessionManager::set(
                'csrf_token',
                bin2hex(random_bytes(32))
            );
        }

        return SessionManager::get('csrf_token');
    }

    public static function verify(?string $token): bool
    {
        SessionManager::start();

        $sessionToken = SessionManager::get('csrf_token');

        if (!$sessionToken || !$token) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}