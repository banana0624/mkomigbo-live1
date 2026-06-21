<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    /**
     * Initialize PDO connection
     */
    public static function init(): void
    {
        if (self::$pdo instanceof PDO) {
            return;
        }

        $host = 'localhost';
        $db   = 'mkomigbo_mkomigbo_app';
        $user = 'mkomigbo_appuser';
        $pass = 'Amuzi_1_Umu_2_Ori_3';

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $host,
            $db
        );

        try {

            self::$pdo = new PDO(
                $dsn,
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );

        } catch (PDOException $e) {

            http_response_code(500);

            die(
                'DATABASE CONNECTION FAILED: ' .
                $e->getMessage()
            );
        }
    }

    /**
     * Get PDO instance
     */
    public static function pdo(): PDO
    {
        if (!(self::$pdo instanceof PDO)) {

            throw new RuntimeException(
                'Database not initialized.'
            );
        }

        return self::$pdo;
    }
}