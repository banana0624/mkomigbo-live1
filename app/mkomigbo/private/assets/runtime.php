<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ZERO-500 RUNTIME LAYER
|--------------------------------------------------------------------------
| Guarantees controlled fallback instead of full HTTP 500 collapse
*/

/*
|--------------------------------------------------------------------------
| 1. SAFE BOOTSTRAP WRAPPER
|--------------------------------------------------------------------------
*/
function mk_bootstrap(callable $callback)
{
    try {
        return $callback();
    } catch (Throwable $e) {

        // Log error safely
        error_log('[BOOTSTRAP ERROR] ' . $e->getMessage());

        // Safe fallback response
        http_response_code(500);

        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo "<pre style='color:red'>";
            echo "SYSTEM RECOVERED FROM CRASH\n\n";
            echo $e->getMessage() . "\n";
            echo "</pre>";
        } else {
            echo "Service temporarily unavailable.";
        }

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| 2. ENV CACHE LAYER (FAST BOOT OPTIMIZATION)
|--------------------------------------------------------------------------
*/
function mk_env_cached(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $cacheFile = APP_ROOT . '/storage/cache/env.php';

    if (is_file($cacheFile)) {
        $cache = include $cacheFile;
        return $cache;
    }

    // fallback to runtime env
    $cache = $_ENV;

    return $cache;
}

/*
|--------------------------------------------------------------------------
| 3. DB RETRY LAYER (RESILIENT CONNECTION)
|--------------------------------------------------------------------------
*/
function mk_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $env = mk_env_cached();

    $host = $env['DB_HOST'] ?? null;
    $name = $env['DB_NAME'] ?? null;
    $user = $env['DB_USER'] ?? null;
    $pass = $env['DB_PASS'] ?? '';

    if (!$host || !$name || !$user) {
        throw new RuntimeException("DB config missing in runtime layer.");
    }

    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

    $attempts = 0;
    $maxAttempts = 3;

    while ($attempts < $maxAttempts) {
        try {
            $pdo = new PDO(
                $dsn,
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => false
                ]
            );

            return $pdo;

        } catch (Throwable $e) {
            $attempts++;
            usleep(200000); // 200ms backoff
        }
    }

    throw new RuntimeException("Database unavailable after retries.");
}

/*
|--------------------------------------------------------------------------
| 4. SAFE VIEW FALLBACK SYSTEM
|--------------------------------------------------------------------------
*/
function mk_safe_view(string $viewFile, array $data = []): void
{
    try {

        if (!is_file($viewFile)) {
            throw new RuntimeException("View missing: " . $viewFile);
        }

        extract($data, EXTR_SKIP);
        require $viewFile;

    } catch (Throwable $e) {

        error_log('[VIEW ERROR] ' . $e->getMessage());

        http_response_code(500);

        echo "<div style='padding:20px;font-family:Arial'>";
        echo "<h3>Temporary Rendering Issue</h3>";

        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo "<pre>" . $e->getMessage() . "</pre>";
        }

        echo "</div>";
    }
}

/*
|--------------------------------------------------------------------------
| 5. GLOBAL SAFETY HELPER
|--------------------------------------------------------------------------
*/
function mk_fail_safe(string $message = 'Unexpected error'): void
{
    http_response_code(500);

    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<pre>SAFE MODE ERROR:\n{$message}</pre>";
    } else {
        echo "Service temporarily unavailable.";
    }

    exit;
}