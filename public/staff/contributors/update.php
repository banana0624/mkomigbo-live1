<?php
declare(strict_types=1);

/**
 * /public/staff/contributors/update.php
 * Staff: Update contributor (POST-only)
 *
 * Hardened:
 * - Schema-tolerant: updates only columns that exist
 * - CSRF protected (csrf_token)
 * - Slug safety:
 *    - Keeps existing slug unless user explicitly provides a new slug
 *    - If provided, slugify + unique (excluding current id)
 * - Bio safety:
 *    - Sanitizes bio_raw -> bio_html using mk_sanitize_bio_html / mk_sanitize_allowlist_html when available
 *    - Else generates safe HTML (escaped + nl2br) so no XSS
 * - Roles normalized to JSON array string
 * - No arrow functions
 */

require_once __DIR__ . '/../_init.php';

if (function_exists('require_staff_login')) { require_staff_login(); }
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

/* ---------------------------------------------------------
   Basic helpers
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

/* CSRF (field name: csrf_token) */
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

/* Safe return (staff-only) */
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

/* Schema helpers */
if (!function_exists('pf__table_exists')) {
  function pf__table_exists(PDO $pdo, string $table): bool {
    try {
      $st = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1");
      $st->execute([$table]);
      return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
      return false;
    }
  }
}
if (!function_exists('pf__column_exists')) {
  function pf__column_exists(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $k = strtolower($table . '.' . $column);
    if (array_key_exists($k, $cache)) return (bool)$cache[$k];
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
    $st->execute([$table, $column]);
    $cache[$k] = ((int)$st->fetchColumn() > 0);
    return (bool)$cache[$k];
  }
}

/* Roles normalization -> JSON string */
if (!function_exists('pf__normalize_roles')) {
  function pf__normalize_roles(string $raw): array {
    $raw = trim($raw);
    if ($raw === '') return ['ok' => true, 'value' => '[]'];

    if (isset($raw[0]) && $raw[0] === '[') {
      $decoded = json_decode($raw, true);
      if (!is_array($decoded)) {
        return [
          'ok' => false,
          'value' => '',
          'error' => 'Roles JSON is invalid. Use CSV (Author,Editor) or JSON array ["Author","Editor"].'
        ];
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

/* Failsafe bio HTML (always safe) */
if (!function_exists('mk_failsafe_bio_html')) {
  function mk_failsafe_bio_html(string $raw): string {
    $raw = trim($raw);
    if ($raw === '') return '';
    $escaped = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    return nl2br($escaped, false);
  }
}

/* Slugify fallback */
if (!function_exists('mk_slugify')) {
  function mk_slugify(string $raw, string $fallback = 'contributor'): string {
    $raw = trim($raw);
    if ($raw === '') return $fallback;
    $raw = strtolower($raw);
    $raw = preg_replace('/[^\p{L}\p{N}]+/u', '-', $raw) ?? $raw;
    $raw = trim($raw, '-');
    return $raw !== '' ? $raw : $fallback;
  }
}

/* Uniqueness fallback (excluding current id) */
if (!function_exists('mk_unique_contributor_slug')) {
  function mk_unique_contributor_slug(PDO $pdo, string $base, int $excludeId): string {
    $base = trim($base);
    if ($base === '') $base = 'contributor';

    $slug = $base;
    for ($i = 0; $i < 60; $i++) {
      $st = $pdo->prepare("SELECT id FROM contributors WHERE slug = ? AND id <> ? LIMIT 1");
      $st->execute([$slug, $excludeId]);
      $found = $st->fetch(PDO::FETCH_ASSOC);

      if (!$found) return $slug;

      $slug = $base . '-' . ($i + 2);
    }
    return $base . '-' . time();
  }
}

/* ---------------------------------------------------------
   Method / CSRF
--------------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  echo "Method Not Allowed";
  exit;
}
csrf_require();

/* Return + id */
$id = (int)($_POST['id'] ?? 0);
$default_return = '/staff/contributors/index.php';
$return = pf__safe_return_url((string)($_POST['return'] ?? $default_return), $default_return);

if ($id <= 0) {
  pf__flash_set('error', 'Invalid contributor id.');
  redirect_to($return);
}

/* DB */
$pdo = function_exists('staff_pdo') ? staff_pdo() : (function_exists('db') ? db() : null);
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}
if (!pf__table_exists($pdo, 'contributors')) {
  pf__flash_set('error', 'contributors table not found.');
  redirect_to($return);
}

/* ---------------------------------------------------------
   Detect columns (schema-tolerant)
--------------------------------------------------------- */
$table = 'contributors';

$cols = [
  'display_name' => pf__column_exists($pdo, $table, 'display_name'),
  'name'         => pf__column_exists($pdo, $table, 'name'),
  'username'     => pf__column_exists($pdo, $table, 'username'),
  'slug'         => pf__column_exists($pdo, $table, 'slug'),
  'email'        => pf__column_exists($pdo, $table, 'email'),
  'roles'        => pf__column_exists($pdo, $table, 'roles'),
  'status'       => pf__column_exists($pdo, $table, 'status'),
  'avatar_path'  => pf__column_exists($pdo, $table, 'avatar_path'),
  'bio_raw'      => pf__column_exists($pdo, $table, 'bio_raw'),
  'bio_html'     => pf__column_exists($pdo, $table, 'bio_html'),
  'bio'          => pf__column_exists($pdo, $table, 'bio'),
];

$pub_col = null;
if (pf__column_exists($pdo, $table, 'is_public')) $pub_col = 'is_public';
elseif (pf__column_exists($pdo, $table, 'visible')) $pub_col = 'visible';

/* ---------------------------------------------------------
   Load existing row (for keeping slug stable)
--------------------------------------------------------- */
$existing = null;
try {
  $select = ['id'];
  $need = ['display_name','name','username','slug','bio_raw','bio','bio_html','status','email','roles','avatar_path'];
  if ($pub_col) $need[] = $pub_col;
  foreach ($need as $c) {
    if ($c === 'id' || pf__column_exists($pdo, $table, $c)) $select[] = '`' . str_replace('`', '', $c) . '`';
  }
  $sql = "SELECT " . implode(', ', array_values(array_unique($select))) . " FROM contributors WHERE id = ? LIMIT 1";
  $st = $pdo->prepare($sql);
  $st->execute([$id]);
  $existing = $st->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
  $existing = null;
}

if (!$existing) {
  pf__flash_set('error', 'Contributor not found.');
  redirect_to($return);
}

/* ---------------------------------------------------------
   Read inputs
--------------------------------------------------------- */
$display_name = trim((string)($_POST['display_name'] ?? ''));
$name         = trim((string)($_POST['name'] ?? ''));
$username     = trim((string)($_POST['username'] ?? ''));
$slug_input   = trim((string)($_POST['slug'] ?? ''));
$email        = trim((string)($_POST['email'] ?? ''));
$roles_raw    = (string)($_POST['roles'] ?? '');
$status_raw   = strtolower(trim((string)($_POST['status'] ?? '')));
$avatar_path  = trim((string)($_POST['avatar_path'] ?? ''));
$bio_raw_in   = (string)($_POST['bio_raw'] ?? '');

/* Required display_name (if your schema requires it) */
if ($cols['display_name'] && $display_name === '') {
  pf__flash_set('error', 'Display name is required.');
  redirect_to('/staff/contributors/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return));
}

/* Status validation (only accept active/draft) */
$status = '';
if ($cols['status']) {
  if ($status_raw === '') $status_raw = 'active';
  if ($status_raw !== 'active' && $status_raw !== 'draft') {
    pf__flash_set('error', 'Invalid status. Use active or draft.');
    redirect_to('/staff/contributors/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return));
  }
  $status = $status_raw;
}

/* Public checkbox (if supported) */
$pub_val = null;
if ($pub_col) {
  $pub_val = (isset($_POST[$pub_col]) && (string)$_POST[$pub_col] === '1') ? 1 : 0;
}

/* Roles */
$roles_json = null;
if ($cols['roles']) {
  $roles_norm = pf__normalize_roles($roles_raw);
  if (!$roles_norm['ok']) {
    pf__flash_set('error', $roles_norm['error'] ?? 'Invalid roles value.');
    redirect_to('/staff/contributors/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return));
  }
  $roles_json = $roles_norm['value'];
}

/* ---------------------------------------------------------
   Slug logic (stable unless user provides a new one)
--------------------------------------------------------- */
$slug_final = null;
if ($cols['slug']) {
  $current_slug = trim((string)($existing['slug'] ?? ''));

  if ($slug_input !== '') {
    $base = mk_slugify($slug_input, 'contributor');
    $slug_final = mk_unique_contributor_slug($pdo, $base, $id);
  } else {
    // Do not churn slug on unrelated edits: keep current
    if ($current_slug !== '') {
      $slug_final = $current_slug;
    } else {
      // If existing slug is empty, generate one from best available label
      $base_source = $display_name !== '' ? $display_name : ($name !== '' ? $name : ($username !== '' ? $username : 'contributor'));
      $base = mk_slugify($base_source, 'contributor');
      $slug_final = mk_unique_contributor_slug($pdo, $base, $id);
    }
  }
}

/* ---------------------------------------------------------
   Bio sanitize (bio_raw -> bio_html) always safe
--------------------------------------------------------- */
$bio_raw_to_save  = $bio_raw_in;
$bio_html_to_save = null;

if ($cols['bio_raw'] || $cols['bio_html'] || $cols['bio']) {
  // Load sanitize helper if your project has it
  if (!function_exists('mk_sanitize_allowlist_html')) {
    $san = defined('APP_ROOT') ? (APP_ROOT . '/private/functions/sanitize.php') : null;
    if ($san && is_file($san)) require_once $san;
  }

  if (function_exists('mk_sanitize_bio_html')) {
    $bio_html_to_save = mk_sanitize_bio_html($bio_raw_to_save);
  } elseif (function_exists('mk_sanitize_allowlist_html')) {
    $bio_html_to_save = mk_sanitize_allowlist_html($bio_raw_to_save);
  } else {
    $bio_html_to_save = mk_failsafe_bio_html($bio_raw_to_save);
  }
}

/* ---------------------------------------------------------
   Build UPDATE
--------------------------------------------------------- */
$set = [];
$params = [':id' => $id];

/* Basic fields */
if ($cols['display_name']) { $set[] = 'display_name = :display_name'; $params[':display_name'] = $display_name; }
if ($cols['name'])         { $set[] = 'name = :name';                 $params[':name'] = ($name === '' ? null : $name); }
if ($cols['username'])     { $set[] = 'username = :username';         $params[':username'] = ($username === '' ? null : $username); }
if ($cols['email'])        { $set[] = 'email = :email';               $params[':email'] = ($email === '' ? null : $email); }
if ($cols['avatar_path'])  { $set[] = 'avatar_path = :avatar_path';   $params[':avatar_path'] = ($avatar_path === '' ? null : $avatar_path); }

if ($cols['status']) {
  $set[] = 'status = :status';
  $params[':status'] = $status;
}

if ($cols['slug']) {
  $set[] = 'slug = :slug';
  $params[':slug'] = (string)$slug_final;
}

if ($cols['roles'] && $roles_json !== null) {
  $set[] = 'roles = :roles';
  $params[':roles'] = $roles_json;
}

if ($pub_col) {
  $set[] = $pub_col . ' = :pub';
  $params[':pub'] = $pub_val;
}

/* Bio fields */
if ($cols['bio_raw']) {
  $set[] = 'bio_raw = :bio_raw';
  $params[':bio_raw'] = $bio_raw_to_save;
} elseif ($cols['bio']) {
  $set[] = 'bio = :bio';
  $params[':bio'] = $bio_raw_to_save;
}

if ($cols['bio_html'] && $bio_html_to_save !== null) {
  $set[] = 'bio_html = :bio_html';
  $params[':bio_html'] = $bio_html_to_save;
}

if (!$set) {
  pf__flash_set('error', 'No writable columns detected.');
  redirect_to($return);
}

/* ---------------------------------------------------------
   Execute
--------------------------------------------------------- */
try {
  $sql = "UPDATE contributors SET " . implode(', ', $set) . " WHERE id = :id LIMIT 1";
  $st = $pdo->prepare($sql);
  $st->execute($params);

  pf__flash_set('notice', 'Contributor updated.');
  redirect_to('/staff/contributors/show.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return));

} catch (Throwable $e) {
  $msg = $e->getMessage();

  if (stripos($msg, 'Duplicate') !== false) {
    if (stripos($msg, 'slug') !== false) {
      pf__flash_set('error', 'Update failed: slug already exists for another contributor.');
    } elseif (stripos($msg, 'email') !== false) {
      pf__flash_set('error', 'Update failed: email already exists for another contributor.');
    } else {
      pf__flash_set('error', 'Update failed: duplicate value.');
    }
  } else {
    pf__flash_set('error', 'Update failed: ' . $msg);
  }

  redirect_to('/staff/contributors/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return));
}
