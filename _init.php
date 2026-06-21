<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Kernel Single-Entry Guard
|--------------------------------------------------------------------------
*/

if (defined('APP_KERNEL_LOADED')) {
    http_response_code(500);
    exit('Kernel duplication detected');
}

define('APP_KERNEL_LOADED', true);
define('APP_KERNEL_PATH', __FILE__);

/*
|--------------------------------------------------------------------------
| Core Constants
|--------------------------------------------------------------------------
*/

define('APP_ROOT', realpath(__DIR__));

/*
|--------------------------------------------------------------------------
| Direct Entry Protection
|--------------------------------------------------------------------------
*/

$script = $_SERVER['SCRIPT_NAME'] ?? '';

if (
    str_contains($script, 'bootstrap.php') ||
    str_contains($script, '_bootstrap.php')
) {
    http_response_code(403);
    exit('Direct bootstrap access forbidden');
}

/*
|--------------------------------------------------------------------------
| Load Core Runtime
|--------------------------------------------------------------------------
*/

require_once APP_ROOT . '/core/ExecutionGraph.php';
require_once APP_ROOT . '/core/BootstrapFirewall.php';
require_once APP_ROOT . '/core/SelfHealingKernel.php';
require_once APP_ROOT . '/core/CompiledKernelLoader.php';

/*
|--------------------------------------------------------------------------
| Initialize Runtime Systems
|--------------------------------------------------------------------------
*/

ExecutionGraph::init();

BootstrapFirewall::registerAllowedRoot(APP_ROOT);

SelfHealingKernel::runScan(APP_ROOT);

/*
|--------------------------------------------------------------------------
| Load Environment
|--------------------------------------------------------------------------
*/

$envFile = APP_ROOT . '/.env';

if (is_file($envFile)) {

    $lines = file(
        $envFile,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

    foreach ($lines as $line) {

        $line = trim($line);

        if (
            $line === '' ||
            str_starts_with($line, '#')
        ) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        $_ENV[$key] = $value;

        putenv($key . '=' . $value);
    }
}

/*
|--------------------------------------------------------------------------
| Plugin Registry
|--------------------------------------------------------------------------
*/

$GLOBALS['APP_PLUGINS'] = [];

/**
 * Register runtime plugin
 */
function register_plugin(callable $plugin): void
{
    $GLOBALS['APP_PLUGINS'][] = $plugin;
}

/**
 * Execute plugins
 */
function boot_plugins(): void
{
    foreach ($GLOBALS['APP_PLUGINS'] as $plugin) {

        try {

            ExecutionGraph::record(
                'plugin:runtime',
                'plugin'
            );

            $plugin();

        } catch (Throwable $e) {

            error_log(
                '[PLUGIN ERROR] ' .
                $e->getMessage()
            );
        }
    }
}

/*
|--------------------------------------------------------------------------
| Safe Require Wrapper
|--------------------------------------------------------------------------
*/

function tracked_require(string $file): void
{
    ExecutionGraph::record(
        $file,
        'require'
    );

    BootstrapFirewall::check(
        $file,
        debug_backtrace(
            DEBUG_BACKTRACE_IGNORE_ARGS,
            2
        )[1]['file'] ?? '__unknown__'
    );

    require_once $file;
}

/*
|--------------------------------------------------------------------------
| Load Compiled Boot Map
|--------------------------------------------------------------------------
*/

$bootMap = APP_ROOT . '/cache/bootmap.php';

if (!is_file($bootMap)) {

    http_response_code(500);

    exit(
        'Compiled boot map missing: ' .
        $bootMap
    );
}

$loader = new CompiledKernelLoader($bootMap);

$loader->load();

$GLOBALS['BOOT_ORDER'] = $loader->getBootOrder();

/*
|--------------------------------------------------------------------------
| Execute Compiled Kernel
|--------------------------------------------------------------------------
*/

$loader->execute();

/*
|--------------------------------------------------------------------------
| Execute Runtime Plugins
|--------------------------------------------------------------------------
*/

boot_plugins();