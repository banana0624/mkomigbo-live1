<?php
declare(strict_types=1);

// /public/staff/contributions/approve.php

require_once __DIR__ . '/../../_init.php';
mk_require_staff_login();

require_once APP_ROOT . '/private/functions/contributions.php';
require_once APP_ROOT . '/private/functions/staff_flash.php';

if (function_exists('mk__session_start')) {
    mk__session_start();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

if (!function_exists('mk_contrib_staff_csrf_verify')) {
    function mk_contrib_staff_csrf_verify(?string $token): bool
    {
        $sessionToken = $_SESSION['staff_contrib_csrf'] ?? '';
        return is_string($sessionToken)
            && $sessionToken !== ''
            && is_string($token)
            && $token !== ''
            && hash_equals($sessionToken, $token);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mk_staff_flash_set('error', 'Invalid request method.');
    header('Location: /staff/contributions/', true, 302);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$note = trim((string)($_POST['moderation_note'] ?? ''));
$token = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : null;

if ($id < 1) {
    mk_staff_flash_set('error', 'Invalid contribution id.');
    header('Location: /staff/contributions/', true, 302);
    exit;
}

if (!mk_contrib_staff_csrf_verify($token)) {
    mk_staff_flash_set('error', 'Invalid CSRF token.');
    header('Location: /staff/contributions/show.php?id=' . $id, true, 302);
    exit;
}

$uid = (int)($_SESSION['staff_user_id'] ?? 0);
    if ($uid < 1) {
        mk_staff_flash_set('error', 'Not authorized.');
        header('Location: /staff/contributions/show.php?id=' . $id, true, 302);
        exit;
    }
    
    $row = mk_contribution_find($id);
    if (!$row) {
        mk_staff_flash_set('error', 'Contribution record not found.');
        header('Location: /staff/contributions/', true, 302);
        exit;
    }
    
    if (strtolower((string)($row['status'] ?? '')) === 'deleted') {
        mk_staff_flash_set('error', 'This contribution is already deleted and cannot be moderated further.');
        header('Location: /staff/contributions/show.php?id=' . $id, true, 302);
        exit;
    }
    
    try {
        mk_contribution_set_status($id, 'approved', $uid, $note !== '' ? $note : null);

    if (function_exists('mk_staff_audit_log')) {
        mk_staff_audit_log('contribution_approved', [
            'contribution_id' => $id,
        ], $uid);
    }

    mk_staff_flash_set('success', 'Contribution approved successfully.');
    header('Location: /staff/contributions/show.php?id=' . $id, true, 302);
    exit;
} catch (Throwable $e) {
    mk_staff_flash_set('error', 'Approval failed.');
    header('Location: /staff/contributions/show.php?id=' . $id, true, 302);
    exit;
}