<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| STAFF INIT - SAFE MODE
|--------------------------------------------------------------------------
| No recursion
| No DB dependency
| No external auth dependency required to load page
|--------------------------------------------------------------------------
*/

if (defined('MK_STAFF_INIT_LOADED')) {
    return;
}

define('MK_STAFF_INIT_LOADED', true);

/* ---------------------------------------------------------
| Define APP_ROOT and PRIVATE_PATH (required by tools, etc.)
| public/staff/_init.php is 2 levels below the release root:
|   /home/mkomigbo/releases/2026-04-25-120559/public/staff/_init.php
|   dirname(__DIR__, 2) = /home/mkomigbo/releases/2026-04-25-120559
--------------------------------------------------------- */
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

if (!defined('PRIVATE_PATH')) {
    define('PRIVATE_PATH', APP_ROOT . '/app/mkomigbo/private');
}

/* ---------------------------------------------------------
| Session SAFE START
--------------------------------------------------------- */
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

/* ---------------------------------------------------------
| SAFE AUTH GUARD (NO external function dependency)
--------------------------------------------------------- */
if (!function_exists('auth_require_role')) {

    function auth_require_role(string $role): void
    {
        if ($role !== 'staff') {
            return;
        }

        $loggedIn =
            !empty($_SESSION['staff_user_id']) ||
            !empty($_SESSION['staff_user']) ||
            !empty($_SESSION['staff_id']);

        if (!$loggedIn) {
            header('Location: /staff/login.php', true, 302);
            exit;
        }
    }
}

/* ---------------------------------------------------------
| Minimal helpers
--------------------------------------------------------- */

// Load DB functions (required for db() and staff_pdo())
$_db_path = APP_ROOT . '/app/mkomigbo/private/functions/db.php';
if (is_file($_db_path)) {
    require_once $_db_path;
}
unset($_db_path);

if (!function_exists('h')) {
    function h(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}

/* ---------------------------------------------------------
| PF__ helper functions (used by all staff pages)
--------------------------------------------------------- */

if (!function_exists("pf__u")) {
    function pf__u(string $path): string {
        return "/" . ltrim(str_replace(["\r","\n"], "", $path), "/");
    }
}

if (!function_exists("pf__column_exists")) {
    function pf__column_exists(PDO $pdo, string $table, string $col): bool {
        try {
            $st = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
            $st->execute([$table, $col]);
            return (bool)$st->fetchColumn();
        } catch (Throwable $e) { return false; }
    }
}

if (!function_exists("pf__page_title_of")) {
    function pf__page_title_of(array $r): string {
        foreach (["title","menu_name","nav_label","name","slug"] as $k) {
            $v = trim((string)($r[$k] ?? ""));
            if ($v !== "") return $v;
        }
        return "#" . ($r["id"] ?? "?");
    }
}

if (!function_exists("pf__flash_set")) {
    function pf__flash_set(string $key, string $msg): void {
        if (!isset($_SESSION["flash"]) || !is_array($_SESSION["flash"])) $_SESSION["flash"] = [];
        $_SESSION["flash"][$key] = $msg;
    }
}

if (!function_exists("pf__flash_get")) {
    function pf__flash_get(string $key): string {
        $msg = "";
        if (isset($_SESSION["flash"][$key])) {
            $msg = (string)$_SESSION["flash"][$key];
            unset($_SESSION["flash"][$key]);
        }
        return $msg;
    }
}

if (!function_exists("pf__flash_html")) {
    function pf__flash_html(): string {
        $out = "";
        if (!empty($_SESSION["flash"]) && is_array($_SESSION["flash"])) {
            foreach ($_SESSION["flash"] as $k => $v) {
                $type = str_contains($k,"err") ? "danger" : (str_contains($k,"ok") ? "success" : "info");
                $out .= "<div class=\"alert alert--{$type}\">" . htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8") . "</div>";
            }
            $_SESSION["flash"] = [];
        }
        return $out;
    }
}

if (!function_exists("pf__subject_logo_url")) {
    function pf__subject_logo_url(string $slug): string {
        return "/lib/images/subjects/" . preg_replace("/[^a-z0-9_-]/","",strtolower($slug)) . ".svg";
    }
}

/* ---------------------------------------------------------
| IMPORTANT: DO NOT AUTO-CALL auth_require_role HERE
| Only pages that require protection should call it
--------------------------------------------------------- */