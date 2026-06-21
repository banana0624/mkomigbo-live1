<?php
declare(strict_types=1);

/**
 * Unified Auth Core (Admin + Staff)
 * ----------------------------------
 * Replaces:
 * - mk_* functions
 * - requireLogin()
 * - require_staff_login()
 * - duplicated session logic
 *
 * Single source of truth for identity + RBAC.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ---------------------------------------------------------
   SESSION STRUCTURE (canonical)
--------------------------------------------------------- */
function auth_user(): ?array
{
    return $_SESSION['auth'] ?? null;
}

function auth_id(): int
{
    return (int)($_SESSION['auth']['id'] ?? 0);
}

function auth_role(): string
{
    return (string)($_SESSION['auth']['role'] ?? '');
}

/* ---------------------------------------------------------
   LOGIN STATE
--------------------------------------------------------- */
function auth_check(): bool
{
    return !empty($_SESSION['auth']['id']);
}

/* ---------------------------------------------------------
   LOGIN SETTER (used by admin/staff login handlers)
--------------------------------------------------------- */
function auth_login(int $id, string $role, array $extra = []): void
{
    $_SESSION['auth'] = [
        'id' => $id,
        'role' => $role,
        'capabilities' => $extra['capabilities'] ?? []
    ];
}

/* ---------------------------------------------------------
   LOGOUT
--------------------------------------------------------- */
function auth_logout(): void
{
    unset($_SESSION['auth']);
}

/* ---------------------------------------------------------
   RBAC LOADER (lazy from DB)
--------------------------------------------------------- */
function auth_capabilities(): array
{
    if (!auth_check()) return [];

    if (!empty($_SESSION['auth']['capabilities'])) {
        return $_SESSION['auth']['capabilities'];
    }

    try {
        $pdo = function_exists('db') ? db() : null;
        if (!$pdo) return [];

        $stmt = $pdo->prepare("
            SELECT c.name
            FROM capabilities c
            JOIN role_capabilities rc ON rc.capability_id = c.id
            JOIN roles r ON r.id = rc.role_id
            WHERE r.name = ?
        ");

        $stmt->execute([auth_role()]);
        $caps = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

        $_SESSION['auth']['capabilities'] = $caps;

        return $caps;
    } catch (Throwable $e) {
        return [];
    }
}

/* ---------------------------------------------------------
   PERMISSION CHECKS
--------------------------------------------------------- */
function auth_can(string $capability): bool
{
    return in_array($capability, auth_capabilities(), true);
}

function auth_require(string $capability): void
{
    if (!auth_check()) {
        header("Location: /staff/login.php");
        exit;
    }

    if (!auth_can($capability)) {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}

/* ---------------------------------------------------------
   ROLE CHECK
--------------------------------------------------------- */
function auth_require_role(string $role): void
{
    if (!auth_check()) {
        header("Location: /staff/login.php");
        exit;
    }

    if (auth_role() !== $role) {
        http_response_code(403);
        echo "Access denied";
        exit;
    }
}

/* ---------------------------------------------------------
   BACKWARD COMPATIBILITY LAYER (temporary)
--------------------------------------------------------- */
function mk_require_staff_login(): void
{
    if (!auth_check() || auth_role() !== 'staff') {
        header("Location: /staff/login.php");
        exit;
    }
}

function requireLogin(): void
{
    if (!auth_check() || auth_role() !== 'admin') {
        header("Location: /admin/login.php");
        exit;
    }
}