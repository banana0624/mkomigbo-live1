<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/app/autoload.php';
require_once APP_ROOT . '/app/helpers.php';

use App\Core\Container;
use App\Core\Database;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

Database::init();
Session::start();

$request = new Request();
$router  = new Router();

use App\Repositories\PageRepository;

Container::bind('pageRepo', fn () => new PageRepository(Database::pdo()));

$router->get('/', function () {
    $repo = Container::make('pageRepo');
    return $repo->findBySlug('home');
});

$router->get('/home', function () {
    $repo = Container::make('pageRepo');
    return $repo->findBySlug('home');
});

$router->get('/about', function () {
    $repo = Container::make('pageRepo');
    return $repo->findBySlug('about');
});

$routeHandler = $router->resolve($request);

if ($routeHandler) {
    $response = Middleware::handle([], $request, $routeHandler);
    if (is_array($response)) {
        mk_render_page($response);
        exit;
    }
    if (is_string($response)) {
        echo $response;
        exit;
    }
}

$path   = trim($request->path ?? '', '/');
$domain = \App\Core\DomainResolver::resolve($path);
if ($domain !== 'passthrough') { echo $domain; }
if ($domain !== 'passthrough') { exit; }
