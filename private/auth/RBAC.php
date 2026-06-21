<?php

declare(strict_types=1);

final class RBAC
{
    public static function allows(string $permission): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $role = $_SESSION['role'] ?? null;

        if (!$role) {
            return false;
        }

        $db = self::db();

        $sql = "
            SELECT COUNT(*) AS total
            FROM role_permissions rp
            INNER JOIN roles r
                ON r.id = rp.role_id
            INNER JOIN permissions p
                ON p.id = rp.permission_id
            WHERE r.slug = :role
              AND p.slug = :permission
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);

        $stmt->execute([
            ':role' => $role,
            ':permission' => $permission
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return ((int)($row['total'] ?? 0)) > 0;
    }

    private static function db(): PDO
    {
        global $db;

        return $db;
    }
}