<?php
declare(strict_types=1);

/**
 * /public/contributors/_shared.php
 * PURE CONSUMER LAYER (no bootstrap ownership)
 */

require_once __DIR__ . '/../_init.php';

/* -----------------------------
   Safety defaults
   ----------------------------- */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

/* -----------------------------
   Helpers
   ----------------------------- */

if (!function_exists('h')) {
    function h(string $v): string {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}

/* -----------------------------
   Column existence check
   ----------------------------- */

if (!function_exists('pf__column_exists')) {
    function pf__column_exists(PDO $pdo, string $table, string $column): bool {
        static $cache = [];

        $key = strtolower($table . '.' . $column);
        if (array_key_exists($key, $cache)) return (bool)$cache[$key];

        try {
            $st = $pdo->prepare("
                SELECT 1
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                LIMIT 1
            ");
            $st->execute([$table, $column]);
            return $cache[$key] = (bool)$st->fetchColumn();
        } catch (Throwable $e) {
            return $cache[$key] = false;
        }
    }
}

/* -----------------------------
   Table existence check
   ----------------------------- */

if (!function_exists('pf__table_exists')) {
    function pf__table_exists(PDO $pdo, string $table): bool {
        try {
            $st = $pdo->prepare("
                SELECT 1
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                LIMIT 1
            ");
            $st->execute([$table]);
            return (bool)$st->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

/* -----------------------------
   Key parser
   ----------------------------- */

if (!function_exists('pf__parse_key')) {
    function pf__parse_key(string $raw): array {
        $raw = trim($raw);
        $raw = preg_replace('~[^\w\-]+~u', '', $raw) ?? $raw;
        $raw = trim($raw, '-_');

        if ($raw === '') {
            return ['id' => null, 'slug' => null, 'key' => ''];
        }

        if (ctype_digit($raw)) {
            return ['id' => (int)$raw, 'slug' => null, 'key' => $raw];
        }

        $slug = strtolower($raw);
        $slug = preg_replace('~[^a-z0-9\-]+~', '-', $slug) ?? $slug;
        $slug = preg_replace('~\-{2,}~', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');

        return ['id' => null, 'slug' => $slug, 'key' => $slug];
    }
}

/* -----------------------------
   Contributors header/footer
   ----------------------------- */

if (!function_exists('pf__contributors_header')) {
    function pf__contributors_header(): void {
        if (!defined('APP_ROOT')) {
            http_response_code(500);
            exit("APP_ROOT missing");
        }

        $paths = [
            APP_ROOT . '/private/shared/contributor_header.php',
            APP_ROOT . '/private/shared/public_header.php',
        ];

        foreach ($paths as $p) {
            if (is_file($p)) {
                require_once $p;
                return;
            }
        }

        http_response_code(500);
        exit("Header include missing");
    }
}

if (!function_exists('pf__contributors_footer')) {
    function pf__contributors_footer(): void {
        if (!defined('APP_ROOT')) {
            echo "</body></html>";
            return;
        }

        $paths = [
            APP_ROOT . '/private/shared/contributor_footer.php',
            APP_ROOT . '/private/shared/public_footer.php',
        ];

        foreach ($paths as $p) {
            if (is_file($p)) {
                require_once $p;
                return;
            }
        }

        echo "</body></html>";
    }
}