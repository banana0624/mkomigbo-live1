<?php

declare(strict_types=1);

final class Gate
{
    public static function allows(string $permission): bool
    {
        return RBAC::allows($permission);
    }
}