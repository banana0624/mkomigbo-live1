<?php

class AdminController
{
    public function handle(array $segments): void
    {
        // /admin
        if (count($segments) === 1) {
            echo "<h1>Admin Dashboard</h1>";
            return;
        }

        // /admin/something
        $sub = $segments[1];

        echo "<h1>Admin: " . htmlspecialchars($sub) . "</h1>";
    }
}