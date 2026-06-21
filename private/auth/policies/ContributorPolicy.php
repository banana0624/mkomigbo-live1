<?php

declare(strict_types=1);

final class ContributorPolicy
{
    public static function access(): bool
    {
        return Gate::allows('access_contributor_area');
    }
}