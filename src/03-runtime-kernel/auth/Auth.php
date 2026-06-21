<?php

require_once __DIR__ . '/SessionManager.php';

class Auth
{
    public static function user()
    {
        SessionManager::start();

        return SessionManager::get('user');
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id()
    {
        $user = self::user();

        return $user['id'] ?? null;
    }

    public static function role()
    {
        $user = self::user();

        return $user['role'] ?? null;
    }
}