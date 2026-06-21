<?php

declare(strict_types=1);

final class StaffPolicy
{
    public static function access(): bool
    {
        return Gate::allows('access_staff');
    }

    public static function audit(): bool
    {
        return Gate::allows('access_staff');
    }
}
