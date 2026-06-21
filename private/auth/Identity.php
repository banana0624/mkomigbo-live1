<?php

declare(strict_types=1);

final class Identity
{
    public static function id(): int
    {
        return (int)($_SESSION['staff_user_id'] ?? 0);
    }

    public static function check(): bool
    {
        return self::id() > 0;
    }

    public static function roles(): array
    {
        return RBAC::rolesForUser(self::id());
    }

    public static function permissions(): array
    {
        return RBAC::permissionsForUser(self::id());
    }

    public static function hasRole(string $role): bool
    {
        return in_array($role, self::roles(), true);
    }

    public static function can(string $permission): bool
    {
        return in_array($permission, self::permissions(), true);
    }

    public static function user(): array
    {
        return [
            'id' => self::id(),
            'roles' => self::roles(),
            'permissions' => self::permissions(),
        ];
    }
}