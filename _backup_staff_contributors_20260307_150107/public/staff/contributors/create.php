<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

mk_require_staff_login();

/**
 * /public/staff/contributors/create.php
 * Staff: Create contributor (POST-only, schema-tolerant)
 *
 * - CSRF protected
 * - Roles normalized to JSON array (if roles column exists)
 * - Slug: optional; generated + made unique (if slug column exists)
 * - Bio: stores bio_raw; generates safe bio_html (sanitized/failsafe)
 * - No arrow functions
 */

if (function_exists('require_staff_login')) { require_staff_login(); }

/* Optional project helper */
if (defined('PRIVATE_PATH') && is_file(PRIVATE_PATH . '/functions/slug.php')) {
  require_once PRIVATE_PATH . '/functions/slug.php';
}

/* ---------------------------------------------------------
   Helpers
--------------------------------------------------------- */
if (!function_exists('redirect_to')) {
  function redirect_to(string $loc): void {
    $loc = str_replace(["\r", "\n"], '', $loc);
    header('Location: ' . $loc, true, 302);
    exit;
  }
}
if (!function_exists('pf__flash_set')) {
  function pf__flash_set(string $key, string $msg): void {
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) $_SESSION['flash'] = [];
    $_SESSION['flash'][$key] = $msg;
  }
}
if (!function_exists('pf__safe_return_url')) {
  function pf__safe_return_url(string $raw, string $default): string {
    $raw = trim($raw);
    if ($raw === '') return $default;
    $raw = rawurldecode($raw);
    if ($raw === '' || $raw[0] !== '/') return $default;
    if (preg_match('~^//~', $raw)) return $default;
    if (preg_match('~^[a-z]+:~i', $raw)) return $default;
    if (!preg_match('~^/staff/~', $raw)) return $default;
    return $raw;
  }
}

/* CSRF */
if (!function_exists('csrf_require')) {
  function csrf_require(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    $sent = (string)($_POST['csrf_token'] ?? '');
    $sess = (string)($_SESSION['csrf_token'] ?? '');
    if ($sent === '' || $sess === '' || !hash_equals($sess, $sent)) {
      http_response_code(403);
      header('Content-Type: text/plain; charset=utf-8');
      echo "Invalid CSRF token.";
      exit;
    }
  }
}

/* Schema */
if (!function_exists('mk_table_exists')) {
  function mk_table_exists(PDO $db, string $table): bool {
    $st = $db->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1");
    $st->execute([$table]);
    return (bool)$st->fetchColumn();
  }
}
if (!function_exists('mk_column_exists')) {
  function mk_column_exists(PDO $db, string $table, string $column): bool {
    $st = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
    $st->execute([$table, $column]);
    return ((int)$st->fetchColumn() > 0);
  }
}

/**
 * Normalize roles into a JSON array string.
 * - Accepts CSV: "Author, Editor"
 * - Accepts JSON array: ["Author","Editor"]
 * - Blank => "[]"
 */
if (!function_exists('pf__normalize_roles')) {
  function pf__normalize_roles(string $raw): array {
    $raw = trim($raw);

    if ($raw === '') {
      return ['ok' => true, 'value' => '[]'];
    }

    if (isset($raw[0]) && $raw[0] === '[') {
      $decoded = json_decode($raw, true);
      if (!is_array($decoded)) {
        return ['ok' => false, 'value' => '', 'error' => 'Roles JSON is invalid. Use CSV (Author,Editor) or JSON array ["Author","Editor"].'];
      }
      $out = [];
      foreach ($decoded as $v) {
        $v = trim((string)$v);
        if ($v !== '') $out[] = $v;
      }
      $out = array_values(array_unique($out));
      return ['ok' => true, 'value' => json_encode($out, JSON_UNESCAPED_UNICODE)];
    }

    $parts = preg_split('/\s*,\s*/', $raw) ?: [];
    $out = [];
    foreach ($parts as $p) {
      $p = trim((string)$p);
      if ($p !== '') $out[] = $p;
    }
    $out = array_values(array_unique($out));
    return ['ok' => true, 'value' => json_encode($out, JSON_UNESCAPED_UNICODE)];
  }
}

/* Failsafe: always safe HTML (no XSS) */
if (!function_exists('mk_failsafe_bio_html')) {
  function mk_failsafe_bio_html(string $raw): string {
    $raw = trim($raw);
    if ($raw === '') return '';
    $escaped = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    return nl2br($escaped, false);
  }
}

/* Slug fallback */
if (!function_exists('mk_slugify')) {
  function mk_slugify(string $raw, string $fallback = 'contributor'): string {
    $raw = trim($raw);
    if ($raw === '') return $fallback;
    $raw = strtolower($raw);
    $raw = preg_replace('/[^a-z0-9]+/i', '-', $raw) ?? $raw;
    $raw = trim($raw, '-');
    return $raw !== '' ? $raw : $fallback;
  }
}

/* Make unique contributor slug (fallback if project helper missing) */
if (!function_exists('mk_unique_contributor_slug')) {
  function mk_unique_contributor_slug(PDO $pdo, string $base, ?int $excludeId = null): string {
    $base = trim($base);
    if ($base === '') $base = 'contributor';

    $slug = $base;
    for ($i = 0; $i < 50; $i++) {
      $check = $slug;
      $sql = "SELECT id FROM contributors WHERE slug = ? ";
      $params = [$check];

      if ($excludeId !== null) {
        $sql .= "AND id <> ? ";
        $params[] = $excludeId;
      }
      $sql .= "LIMIT 1";

      $st = $pdo->prepare($sql);
      $st->execute($params);
      $found = $st->fetch(PDO::FETCH_ASSOC);

      if (!$found) return $slug;

      $slug = $base . '-' . ($i + 2);
    }
    return $base . '-' . time();
  }
}

/* ---------------------------------------------------------
   Method
--------------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  echo "Method Not Allowed";
  exit;
}
csrf_require();

$default_return = '/staff/contributors/index.php';
$return = pf__safe_return_url((string)($_POST['return'] ?? $default_return), $default_return);

/* DB */
$pdo = function_exists('staff_pdo') ? staff_pdo() : (function_exists('db') ? db() : null);
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

if (!mk_table_exists($pdo, 'contributors')) {
  pf__flash_set('error', 'contributors table not found.');
  redirect_to($return);
}

/* Detect columns */
$cols = [
  'display_name' => mk_column_exists($pdo, 'contributors', 'display_name'),
  'name'         => mk_column_exists($pdo, 'contributors', 'name'),
  'username'     => mk_column_exists($pdo, 'contributors', 'username'),
  'email'        => mk_column_exists($pdo, 'contributors', 'email'),
  'roles'        => mk_column_exists($pdo, 'contributors', 'roles'),
  'status'       => mk_column_exists($pdo, 'contributors', 'status'),
  'slug'         => mk_column_exists($pdo, 'contributors', 'slug'),
  'bio_raw'      => mk_column_exists($pdo, 'contributors', 'bio_raw'),
  'bio_html'     => mk_column_exists($pdo, 'contributors', 'bio_html'),
  'bio'          => mk_column_exists($pdo, 'contributors', 'bio'),
  'avatar_path'  => mk_column_exists($pdo, 'contributors', 'avatar_path'),
];

$pub_col = null;
if (mk_column_exists($pdo, 'contributors', 'is_public')) $pub_col = 'is_public';
elseif (mk_column_exists($pdo, 'contributors', 'visible')) $pub_col = 'visible';

/* Read inputs */
$display_name = trim((string)($_POST['display_name'] ?? ''));
$name         = trim((string)($_POST['name'] ?? ''));
$username     = trim((string)($_POST['username'] ?? ''));
$email        = trim((string)($_POST['email'] ?? ''));
$roles_raw    = (string)($_POST['roles'] ?? '');
$status       = trim((string)($_POST['status'] ?? 'active'));
$slug_input   = trim((string)($_POST['slug'] ?? ''));
$avatar_path  = trim((string)($_POST['avatar_path'] ?? ''));

/* Validate required-ish */
$label_for_slug = $display_name !== '' ? $display_name : ($name !== '' ? $name : ($username !== '' ? $username : ''));
if ($cols['display_name'] && $display_name === '') {
  pf__flash_set('error', 'Display name is required.');
  redirect_to('/staff/contributors/new.php?return=' . rawurlencode($return));
}

/* roles normalization */
$roles_norm = ['ok' => true, 'value' => '[]'];
if ($cols['roles']) {
  $roles_norm = pf__normalize_roles($roles_raw);
  if (!$roles_norm['ok']) {
    pf__flash_set('error', $roles_norm['error'] ?? 'Invalid roles value.');
    redirect_to('/staff/contributors/new.php?return=' . rawurlencode($return));
  }
}

/* Public checkbox (if supported) */
$pub_val = null;
if ($pub_col) {
  $pub_val = (isset($_POST[$pub_col]) && (string)$_POST[$pub_col] === '1') ? 1 : 0;
}

/* bio sanitize */
$bio_raw  = '';
$bio_html = '';
if ($cols['bio_raw'] || $cols['bio_html'] || $cols['bio']) {
  $bio_raw = (string)($_POST['bio_raw'] ?? '');

  if (function_exists('mk_sanitize_bio_html')) {
    $bio_html = mk_sanitize_bio_html($bio_raw);
  } elseif (function_exists('mk_sanitize_allowlist_html')) {
    $bio_html = mk_sanitize_allowlist_html($bio_raw);
  } else {
    $bio_html = mk_failsafe_bio_html($bio_raw);
  }
}

/* slug generation */
$slug_final = '';
if ($cols['slug']) {
  $base = ($slug_input !== '') ? mk_slugify($slug_input, 'contributor') : mk_slugify($label_for_slug, 'contributor');

  if (function_exists('mk_slug_unique')) {
    // If your slug.php offers mk_slug_unique(PDO $db, string $table, string $slug, ...), use it.
    try {
      $slug_final = mk_slug_unique($pdo, 'contributors', $base);
    } catch (Throwable $e) {
      $slug_final = mk_unique_contributor_slug($pdo, $base, null);
    }
  } else {
    $slug_final = mk_unique_contributor_slug($pdo, $base, null);
  }
}

try {
  $fields = [];
  $params = [];

  if ($cols['display_name']) { $fields[] = 'display_name'; $params[':display_name'] = $display_name; }
  if ($cols['name'])         { $fields[] = 'name';         $params[':name'] = ($name === '' ? null : $name); }
  if ($cols['username'])     { $fields[] = 'username';     $params[':username'] = ($username === '' ? null : $username); }
  if ($cols['slug'])         { $fields[] = 'slug';         $params[':slug'] = $slug_final; }
  if ($cols['email'])        { $fields[] = 'email';        $params[':email'] = ($email === '' ? null : $email); }
  if ($cols['roles'])        { $fields[] = 'roles';        $params[':roles'] = $roles_norm['value']; }
  if ($cols['status'])       { $fields[] = 'status';       $params[':status'] = ($status === '' ? 'active' : $status); }
  if ($cols['avatar_path'])  { $fields[] = 'avatar_path';  $params[':avatar_path'] = ($avatar_path === '' ? null : $avatar_path); }
  if ($pub_col)              { $fields[] = $pub_col;       $params[':pub'] = $pub_val; }
  if ($cols['bio_raw'])      { $fields[] = 'bio_raw';      $params[':bio_raw'] = $bio_raw; }
  if ($cols['bio_html'])     { $fields[] = 'bio_html';     $params[':bio_html'] = $bio_html; }
  if (!$cols['bio_raw'] && $cols['bio']) { $fields[] = 'bio'; $params[':bio'] = $bio_raw; }

  if (!$fields) {
    throw new RuntimeException('No writable columns detected for contributors.');
  }

  $placeholders = [];
  foreach ($fields as $f) {
    if ($pub_col && $f === $pub_col) $placeholders[] = ':pub';
    else $placeholders[] = ':' . $f;
  }

  $sql = "INSERT INTO contributors (" . implode(', ', $fields) . ")
          VALUES (" . implode(', ', $placeholders) . ")";
  $st = $pdo->prepare($sql);
  $st->execute($params);

  $new_id = (int)$pdo->lastInsertId();
  pf__flash_set('notice', 'Contributor created.');
  redirect_to('/staff/contributors/show.php?id=' . rawurlencode((string)$new_id) . '&return=' . rawurlencode($return));

} catch (Throwable $e) {
  pf__flash_set('error', 'Create failed: ' . $e->getMessage());
  redirect_to('/staff/contributors/new.php?return=' . rawurlencode($return));
}
