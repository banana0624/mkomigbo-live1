<?php

declare(strict_types=1);

final class ControllerRegistry
{
    /**
     * Central source of truth for all controllers
     */
    private static array $controllers = [
        'home'        => 'HomeController',
        'admin'       => 'AdminController',
        'platform'    => 'PlatformController',
        'contributor' => 'ContributorController',
        'calendar'    => 'CalendarController',

        // STAFF MODULE
        'staff'       => 'StaffController',
        'staff_audit' => 'StaffAuditController',

        // OPTIONAL / FUTURE SAFE
        'subject'     => 'SubjectController',
    ];

    /**
     * Resolve controller safely
     */
    public static function resolve(string $key): ?string
    {
        return self::$controllers[$key] ?? null;
    }

    /**
     * Check if controller exists physically
     */
    public static function exists(string $key): bool
    {
        $class = self::resolve($key);

        if (!$class) {
            return false;
        }

        $path = PRIVATE_PATH . "/controllers/{$class}.php";

        return is_file($path);
    }

    /**
     * Safe loader (prevents fatal crashes)
     */
    public static function load(string $key): bool
    {
        $class = self::resolve($key);

        if (!$class) {
            return false;
        }

        $path = PRIVATE_PATH . "/controllers/{$class}.php";

        if (!is_file($path)) {
            return false;
        }

        require_once $path;
        return true;
    }
}