<?php

declare(strict_types=1);

final class RouteRegistry
{
    public static function routes(): array
    {
        return [

            // =========================
            // PUBLIC DOMAIN
            // =========================
            [
                'path' => '/',
                'controller' => HomeController::class,
                'method' => 'index',
                'middleware' => [],
                'domain' => 'public',
            ],
            [
                'path' => '/subjects',
                'controller' => SubjectController::class,
                'method' => 'index',
                'middleware' => [],
                'domain' => 'public',
            ],
            [
                'path' => '/igbo-calendar',
                'controller' => CalendarController::class,
                'method' => 'index',
                'middleware' => [],
                'domain' => 'public',
            ],

            // =========================
            // ADMIN DOMAIN
            // =========================
            [
                'path' => '/admin-panel',
                'controller' => AdminController::class,
                'method' => 'index',
                'middleware' => ['auth', 'admin'],
                'domain' => 'admin',
            ],

            // =========================
            // STAFF DOMAIN
            // =========================
            [
                'path' => '/staff',
                'controller' => StaffController::class,
                'method' => 'index',
                'middleware' => ['auth', 'staff'],
                'domain' => 'staff',
            ],
            [
                'path' => '/staff/audit',
                'controller' => StaffController::class,
                'method' => 'audit',
                'middleware' => ['auth', 'staff'],
                'domain' => 'staff',
            ],

            // =========================
            // CONTRIBUTORS DOMAIN
            // =========================
            [
                'path' => '/contributors',
                'controller' => ContributorController::class,
                'method' => 'index',
                'middleware' => ['auth', 'contributor'],
                'domain' => 'identity',
            ],

            // =========================
            // PLATFORMS DOMAIN (HYBRID)
            // =========================
            [
                'path' => '/platforms',
                'controller' => PlatformController::class,
                'method' => 'index',
                'middleware' => ['optional_auth'],
                'domain' => 'hybrid',
            ],
        ];
    }
}