<?php
declare(strict_types=1);

/**
 * STAFF AUTH SYSTEM (STABLE BUILD)
 */

/* ---------------------------
 * SESSION
 * --------------------------- */
if (!function_exists('mk_staff_session_start')) {
function mk_staff_session_start(): void {
    if (function_exists('mk__session_start')) {
        mk__session_start();
        return;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}
}

/* ---------------------------
 * CURRENT USER
 * --------------------------- */
function mk_staff_current_id(): int {
    mk_staff_session_start();
    return (int)($_SESSION['staff_user_id'] ?? 0);
}

/* ---------------------------
 * SESSION VALIDATION
 * --------------------------- */
function mk_staff_session_version_valid(int $uid): bool {
    if ($uid <= 0) return false;

    $sessVer = (int)($_SESSION['staff_session_version'] ?? 0);
    if ($sessVer <= 0) return false;

    $pdo = db();

    $st = $pdo->prepare("
        SELECT session_version
        FROM staff_users
        WHERE id = ?
        LIMIT 1
    ");
    $st->execute([$uid]);

    $dbVer = (int)$st->fetchColumn();

    return ($dbVer > 0 && $dbVer === $sessVer);
}

/* ---------------------------
 * LOGOUT
 * --------------------------- */
function mk_force_staff_logout_local(): void {
    mk_staff_session_start();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'] ?? '/',
            $p['domain'] ?? '',
            (bool)($p['secure'] ?? false),
            (bool)($p['httponly'] ?? true)
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/* ---------------------------
 * AUTH GUARD (FIXED)
 * --------------------------- */
function mk_require_staff_login(): void {
    mk_staff_session_start();

    // Prevent redirect loop
    if (defined('STAFF_LOGIN_PAGE') && STAFF_LOGIN_PAGE === true) {
        return;
    }

    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/staff/');
    $uri = str_replace(["\r", "\n"], '', trim($uri));

    if ($uri === '' || $uri[0] !== '/') {
        $uri = '/staff/';
    }

    // Secondary protection
    if (strpos($uri, '/staff/login.php') !== false) {
        return; // DO NOT redirect login page
    }

    $uid = (int)($_SESSION['staff_user_id'] ?? 0);

    if ($uid <= 0) {
        header('Location: /staff/login.php?return=' . rawurlencode($uri));
        exit;
    }

    if (!mk_staff_session_version_valid($uid)) {
        mk_force_staff_logout_local();

        header('Location: /staff/login.php?forced=1&return=' . rawurlencode($uri));
        exit;
    }
}

/* ---------------------------
 * LOGIN
 * --------------------------- */
function mk_attempt_staff_login(string $email, string $password): array {

    $email = trim($email);
    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'Email and password required'];
    }

    $pdo = db();

    $st = $pdo->prepare("
        SELECT id, email, password_hash, is_active, session_version
        FROM staff_users
        WHERE LOWER(email) = LOWER(?)
        LIMIT 1
    ");
    $st->execute([$email]);

    $u = $st->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        return ['ok' => false, 'error' => 'Invalid credentials'];
    }

    if ((int)$u['is_active'] !== 1) {
        return ['ok' => false, 'error' => 'Account disabled'];
    }

    if (!password_verify($password, $u['password_hash'])) {
        return ['ok' => false, 'error' => 'Invalid credentials'];
    }

    mk_staff_session_start();
    session_regenerate_id(true);

    $_SESSION['staff_user_id'] = (int)$u['id'];
    $_SESSION['staff_email'] = $u['email'];
    $_SESSION['staff_session_version'] = (int)$u['session_version'];

    return ['ok' => true];
}

/* ---------------------------
 * ALIASES
 * --------------------------- */
function require_staff(): void {
    mk_require_staff_login();
}