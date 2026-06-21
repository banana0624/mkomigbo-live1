<?php

declare(strict_types=1);

namespace App\Core;

final class DomainResolver
{
    public static function resolve(string $path): string
    {
        $path = trim($path, '/');

        if (str_starts_with($path, 'subjects')) {
            return 'subjects';
        }

        if (str_starts_with($path, 'platforms')) {
            return 'platforms';
        }

        if (str_starts_with($path, 'contributors')) {
            return 'contributors';
        }

        if (str_starts_with($path, 'igbo-calendar')) {
            return 'passthrough';
        }

        if (str_starts_with($path, 'staff')) {
            return 'staff';
        }

        if (str_starts_with($path, 'admin')) {
            return 'admin';
        }

        return 'cms';
    }
}