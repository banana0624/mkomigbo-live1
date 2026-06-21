<?php

require_once __DIR__ . '/../auth/SessionManager.php';

class GuestMiddleware
{
    public static function handle()
    {
        SessionManager::start();

        if (SessionManager::get('user')) {

            header('Location: /public/index.php/dashboard');

            exit;
        }
    }
}