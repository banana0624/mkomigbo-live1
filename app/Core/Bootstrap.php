<?php

namespace App\Core;

class Bootstrap
{
    public static function init(): void
    {
        // DB
        Database::init();

        // Core services
        App::bind('db', fn() => Database::pdo());

        // Router init (future extension point)
    }
}