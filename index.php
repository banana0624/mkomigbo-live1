<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Response;

/*
|--------------------------------------------------------------------------
| Boot DB
|--------------------------------------------------------------------------
*/

Database::init();

/*
|--------------------------------------------------------------------------
| Core objects
|--------------------------------------------------------------------------
*/

$request = new Request();
$router  = new Router();

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/

$routes = require dirname(__DIR__) . '/routes/web.php';
$routes($router);

/*
|--------------------------------------------------------------------------
| Resolve route
|--------------------------------------------------------------------------
*/

$handler = $router->resolve($request);

if ($handler) {

    $result = $handler($request);

    if ($result) {
        Response::view($result);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Fallback: dynamic page resolution
|--------------------------------------------------------------------------
*/

use App\Repositories\PageRepository;

$repo = new PageRepository(Database::pdo());

$page = $repo->findBySlug($request->path ?: 'home');

if ($page) {
    Response::view($page);
    exit;
}

Response::notFound();