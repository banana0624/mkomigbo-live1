<?php

function dispatch(string $route): void
{
    $route = trim($route, '/');

    switch ($route) {

        // 🏠 HOME
        case '':
        case 'home':
            mk_render_page([
                'slug'  => 'home',
                'title' => 'Home'
            ]);
            return;

        // 👥 CONTRIBUTORS
        case 'contributors':
            require APP_ROOT . '/private/functions/contributors_entry.php';
            return;

        // 🔐 STAFF LOGIN (PUBLIC ENTRY ONLY)
        case 'staff/login':
            require PUBLIC_PATH . '/staff/login.php';
            return;

        // 🛠 STAFF ROOT → redirect to login (no loop)
        case 'staff':
            header('Location: /staff/login.php', true, 302);
            exit;

        // 🧱 ADMIN
        case 'admin':
        case 'admin/dashboard':
            require APP_ROOT . '/private/functions/admin_entry.php';
            return;
    }

    // 📄 Dynamic CMS pages
    $page = mk_find_page_by_slug($route);

    if ($page) {
        mk_render_page($page);
        return;
    }

    // ❌ 404
    http_response_code(404);
    echo "PAGE NOT FOUND";
}