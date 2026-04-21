<?php
declare(strict_types=1);

/* DEFINE ROOT FIRST */
define('APP_ROOT', __DIR__ . '/app/mkomigbo');
define('PRIVATE_PATH', APP_ROOT . '/private/functions');

require_once APP_ROOT . '/private/functions/bootstrap_init.php';

mk_initialize();

/* NORMALIZE PATH */
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);

$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($base !== '' && strpos($path, $base) === 0) {
    $path = substr($path, strlen($base));
}

$path = trim($path, '/');

/* DEBUG */
echo "PATH = [$path]<br>";

/* HOME */
if ($path === '' || $path === 'index.php') {
    mk_render('header');
    mk_render('home');
    mk_render('footer');
    exit;
}

/* CMS */
$page = mk_find_page_by_slug($path);

if ($page) {
    echo "PAGE FOUND<br>";
    mk_render_page($page);
    exit;
}

/* 404 */
echo "PAGE NOT FOUND<br>";
http_response_code(404);