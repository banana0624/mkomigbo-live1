<?php

declare(strict_types=1);

final class AdminPolicy
{
    public static function access(): bool
    {
        return Gate::allows('view_admin_panel');
    }
}