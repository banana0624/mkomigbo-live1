<?php

class Connection
{
    private static $pdo;

    public static function get()
        {
            return new PDO(
                'mysql:host=' . Config::get('DB_HOST') .
                ';dbname=' . Config::get('DB_NAME') .
                ';charset=utf8mb4',
    
                Config::get('DB_USER'),
                Config::get('DB_PASS')
            );
        }
}