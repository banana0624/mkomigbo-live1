<?php

declare(strict_types=1);

final class Ability
{
    public static function check(string $ability): bool
    {
        return Gate::allows($ability);
    }
}