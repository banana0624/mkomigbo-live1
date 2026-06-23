<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../_init.php';

auth_require_role('staff');

/**
 * /public/staff/subjects/pgs/edit.php
 * Staff: Edit a page + manage attachments (page_files).
 *
 * Uses centralized staff bootstrap: /public/staff/_init.php
 *
 * Supports:
 * - schema-tolerant pages columns
 * - optional topic_group (nav grouping)
 * - attachments list + upload/delete
 * - external link add (authoritative normalization via private function)
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
/* ---------------------------------------------------------
   Minimal fallbacks (only if _init.php did not provide them)
--------------------------------------------------------- */
if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('redirect_to')) {
  function redirect_to(string $location, int $code = 302): void {
    $location = str_replace(["\r", "\n"], '', $location);
    header('Location: ' . $location, true, $code);
    exit;
  }
}
if (!function_exists('pf__flash_set')) {
  function pf__flash_set(string $key, string $msg): void {
if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) $_SESSION['flash'] = [];
    $_SESSION['flash'][$key] = $msg;
  }
}
if (!function_exists('pf__flash_get')) {
  function pf__flash_get(string $key): string {
$msg = '';
    if (isset($_SESSION['flash']) && is_array($_SESSION['flash']) && array_key_exists($key, $_SESSION['flash'])) {
      $msg = (string)$_SESSION['flash'][$key];
      unset($_SESSION['flash'][$key]);
    }
    return $msg;
  }
}
if (!function_exists('staff_safe_return_url')) {
  function staff_safe_return_url(string $raw, string $default): string {
    $raw = trim($raw);
    if ($raw === '') return $default;
    $raw = rawurldecode($raw);
    if ($raw === '' || $raw[0] !== '/') return $default;
    if (preg_match('~^//~', $raw)) return $default;
    if (preg_match('~^[a-z]+:~i', $raw)) return $default;
    if (strpos($raw, '/staff/') !== 0) return $default;
    return $raw;
  }
}
if (!function_exists('staff_csrf_verify')) {
  function staff_csrf_verify(string $token): bool {
$sess = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sess) || $sess === '' || $token === '') return false;
    return hash_equals($sess, $token);
  }
}
if (!function_exists('staff_csrf_field')) {
  function staff_csrf_field(): string {
if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . h((string)$_SESSION['csrf_token']) . '">';
  }
}
if (!function_exists('staff_pdo')) {
  function staff_pdo(): ?PDO {
    return (function_exists('db') && db() instanceof PDO) ? db() : null;
  }
}

/**
 * NOTE:
 * Your MySQL user appears to have restricted access to information_schema.COLUMNS (returns empty).
 * So we keep pf__column_exists() for "pages" (often allowed), but for page_files we use a known schema allowlist
 * inside backfill and attachment listing to avoid false negatives.
 */
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
      $cache[$key] = (bool)$st->fetchColumn();
      return (bool)$cache[$key];
    } catch (Throwable $e) {
      $cache[$key] = false;
      return false;
    }
  }
}

$u = static function(string $path): string {
  return function_exists('url_for') ? (string)url_for($path) : $path;
};

if (!function_exists('pf__clean_topic_group')) {
  function pf__clean_topic_group(string $raw): string {
    $v = trim($raw);
    if ($v === '') return '';
    $v = preg_replace('/\s+/u', ' ', $v) ?? $v;
    $v = str_replace(['<','>'], '', $v);
    $v = trim($v);
    if (function_exists('mb_substr')) $v = (string)mb_substr($v, 0, 80, 'UTF-8');
    else $v = substr($v, 0, 80);
    return trim($v);
  }
}

/* ---------------------------------------------------------
   External URL normalization (authoritative input cleaning)
--------------------------------------------------------- */
if (!function_exists('pf__normalize_external_url')) {
  function pf__normalize_external_url(string $url): string {
    $url = preg_replace('/[\x00-\x1F\x7F]/u', '', $url) ?? $url;
    $url = trim($url);
    if ($url === '') return '';

    if ($url[0] === '<' && substr($url, -1) === '>') {
      $url = trim(substr($url, 1, -1));
    }

    if (preg_match('/\s/u', $url)) {
      $parts = preg_split('/\s+/u', $url);
      if (is_array($parts) && isset($parts[0])) $url = trim((string)$parts[0]);
    }

    $url = rtrim($url, " \t\n\r\0\x0B.,;:)]}'\"");

    if (strncmp($url, '//', 2) === 0) {
      $url = 'https:' . $url;
    } elseif (!preg_match('~^[a-zA-Z][a-zA-Z0-9+\-.]*://~', $url)) {
      if ($url !== '' && ($url[0] === '/' || $url[0] === '\\')) return '';
      $url = 'https://' . $url;
    }

    return $url;
  }
}

/* ---------------------------------------------------------
   Backfill external attachment meta (schema-aligned)
--------------------------------------------------------- */
if (!function_exists('pf__infer_external_kind')) {
  function pf__infer_external_kind(string $url, string $host): string {
    $u2 = strtolower($url);
    $h2 = strtolower($host);

    if ($h2 === 'youtu.be' || str_contains($h2, 'youtube.com')) return 'video';
    if (str_contains($h2, 'wikipedia.org')) return 'wiki';
    if (preg_match('~\.pdf([?#]|$)~i', $u2)) return 'pdf';
    if (preg_match('~\.(mp3|wav|m4a)([?#]|$)~i', $u2)) return 'audio';
    if (preg_match('~\.(mp4|webm|mov)([?#]|$)~i', $u2)) return 'video';
    return 'web';
  }
}

if (!function_exists('pf__make_source_key')) {
  function pf__make_source_key(string $host, string $url): string {
    $h2 = strtolower(trim($host));
    $hash = substr(sha1($url), 0, 12);
    $key = $h2 . ':' . $hash;
    return (strlen($key) > 64) ? substr($key, 0, 64) : $key;
  }
}

if (!function_exists('pf__backfill_external_attachment_meta')) {
  /**
   * Backfill fields on page_files external rows using your real schema.
   * This does NOT depend on information_schema (safe on restricted hosting).
   */
  function pf__backfill_external_attachment_meta(PDO $pdo, int $fileId, string $cleanUrl, string $label): void {
    if ($fileId <= 0 || $cleanUrl === '') return;

    $parts = @parse_url($cleanUrl);
    $host = '';
    if (is_array($parts) && !empty($parts['host'])) {
      $host = strtolower(trim((string)$parts['host']));
      $host = preg_replace('/^www\./i', '', $host) ?? $host;
    }

    $kind = pf__infer_external_kind($cleanUrl, $host);
    $label = trim(str_replace(["\r","\n"], '', $label));

    $sets = [];
    $bind = [':id' => $fileId];

    $sets[] = "is_external = 1";
    $sets[] = "external_url = :url";        $bind[':url'] = $cleanUrl;

    if ($host !== '') {
      $sets[] = "external_host = :h1";      $bind[':h1'] = $host;
      $sets[] = "host = :h2";               $bind[':h2'] = $host;
    }

    $sets[] = "canonical_url = :canon";     $bind[':canon'] = $cleanUrl;
    $sets[] = "kind = :kind";               $bind[':kind'] = $kind;

    if ($host !== '') {
      $sets[] = "source_key = COALESCE(source_key, :skey)";
      $bind[':skey'] = pf__make_source_key($host, $cleanUrl);
    }

    if ($label !== '') {
      $sets[] = "source_label = COALESCE(source_label, :lbl1)"; $bind[':lbl1'] = $label;
      $sets[] = "title = COALESCE(title, :lbl2)";               $bind[':lbl2'] = $label;
      $sets[] = "original_name = COALESCE(NULLIF(original_name,''), :lbl3)"; $bind[':lbl3'] = $label;
    } else {
      if ($host !== '') {
        $fallback = 'External link (' . $host . ')';
        $sets[] = "source_label = COALESCE(source_label, :fb1)"; $bind[':fb1'] = $fallback;
        $sets[] = "title = COALESCE(title, :fb2)";               $bind[':fb2'] = $fallback;
        $sets[] = "original_name = COALESCE(NULLIF(original_name,''), :fb3)"; $bind[':fb3'] = $fallback;
      }
    }

    if (!$sets) return;

    $sql = "UPDATE page_files SET " . implode(', ', $sets) . " WHERE id = :id LIMIT 1";
    $st = $pdo->prepare($sql);

    foreach ($bind as $k => $v) {
      if ($v === null) $st->bindValue($k, null, PDO::PARAM_NULL);
      elseif (is_int($v)) $st->bindValue($k, $v, PDO::PARAM_INT);
      else $st->bindValue($k, (string)$v, PDO::PARAM_STR);
    }

    $st->execute();
  }
}

/* ---------------------------------------------------------
   Auth
--------------------------------------------------------- */
auth_require_role('staff');

/* DB */
$pdo = staff_pdo();
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

/* Optional slug helper */
$slugFn = (defined('PRIVATE_PATH') ? (PRIVATE_PATH . '/functions/slug.php') : '');
if ($slugFn !== '' && is_file($slugFn)) { require_once $slugFn; }

/* Inputs */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect_to($u('/staff/subjects/pgs/index.php'));

$return = staff_safe_return_url((string)($_GET['return'] ?? ($_POST['return'] ?? '')), '/staff/subjects/pgs/index.php');

$notice = pf__flash_get('notice');
$error  = pf__flash_get('error');

/* Attachment notices */
$attachNotice = strtolower(trim((string)($_GET['attach'] ?? '')));
$attachMsg = '';
if ($attachNotice === 'sent')      $attachMsg = 'Attachment saved.';
if ($attachNotice === 'partial')   $attachMsg = 'Some files saved; some failed.';
if ($attachNotice === 'error')     $attachMsg = 'Attachment action failed.';
if ($attachNotice === 'deleted')   $attachMsg = 'Attachment deleted.';
if ($attachNotice === 'missing')   $attachMsg = 'Attachment missing (already removed or file not found).';
if ($attachNotice === 'denied')    $attachMsg = 'Action denied.';
if ($attachNotice === 'csrf')      $attachMsg = 'Security check failed. Please retry.';
if ($attachNotice === 'invalid')   $attachMsg = 'Invalid attachment request.';
if ($attachNotice === 'too_large') $attachMsg = 'Upload too large (server rejected POST).';
if ($attachNotice === 'nofile')    $attachMsg = 'No file received by server.';
if ($attachNotice === 'badurl')    $attachMsg = 'External URL rejected (must be HTTPS + allowlisted host/path).';
if ($attachNotice === 'saved')     $attachMsg = 'External link saved.';

/* ---------------------------------------------------------
   Schema: pages
--------------------------------------------------------- */
$has_subject_id  = pf__column_exists($pdo, 'pages', 'subject_id');
$has_slug        = pf__column_exists($pdo, 'pages', 'slug');
$has_topic_group = pf__column_exists($pdo, 'pages', 'topic_group');

$title_col = pf__column_exists($pdo, 'pages', 'title') ? 'title'
           : (pf__column_exists($pdo, 'pages', 'menu_name') ? 'menu_name'
           : (pf__column_exists($pdo, 'pages', 'name') ? 'name' : null));

$body_col  = pf__column_exists($pdo, 'pages', 'body_html') ? 'body_html'
           : (pf__column_exists($pdo, 'pages', 'body') ? 'body'
           : (pf__column_exists($pdo, 'pages', 'content') ? 'content' : null));

$order_col = pf__column_exists($pdo, 'pages', 'nav_order') ? 'nav_order'
           : (pf__column_exists($pdo, 'pages', 'position') ? 'position' : null);

$pub_col   = pf__column_exists($pdo, 'pages', 'is_public') ? 'is_public'
           : (pf__column_exists($pdo, 'pages', 'visible') ? 'visible'
           : (pf__column_exists($pdo, 'pages', 'status') ? 'status' : null));

/* Fetch current page */
$cols = ['id'];
if ($has_subject_id)  $cols[] = 'subject_id';
if ($has_slug)        $cols[] = 'slug';
if ($has_topic_group) $cols[] = 'topic_group';
if ($title_col)       $cols[] = "{$title_col} AS title";
if ($body_col)        $cols[] = "{$body_col} AS body";
if ($order_col)       $cols[] = "{$order_col} AS nav_order";
if ($pub_col)         $cols[] = "{$pub_col} AS pub";

$st = $pdo->prepare("SELECT " . implode(', ', $cols) . " FROM pages WHERE id = :id LIMIT 1");
$st->execute([':id' => $id]);
$page = $st->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$page) {
  pf__flash_set('error', 'Page not found.');
  redirect_to($u($return));
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

/* ---------------------------------------------------------
   Compute page_files existence EARLY
--------------------------------------------------------- */
$pageFilesExists = false;
try {
  $pdo->query("SELECT 1 FROM page_files LIMIT 1");
  $pageFilesExists = true;
} catch (Throwable $e) {
  $pageFilesExists = false;
}

/* ---------------------------------------------------------
   Handle POST: actions
--------------------------------------------------------- */
$action_post = ($method === 'POST') ? trim((string)($_POST['action'] ?? '')) : '';

/* Add external link */
if ($method === 'POST' && $action_post === 'add_external') {

  if (!$pageFilesExists) {
    redirect_to($u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return) . '&attach=error'), 302);
  }

  $token = (string)($_POST['csrf_token'] ?? '');
  if (!staff_csrf_verify($token)) {
    redirect_to($u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return) . '&attach=csrf'), 302);
  }

  $page_id = (int)($_POST['page_id'] ?? 0);
  if ($page_id !== $id || $page_id <= 0) {
    redirect_to($u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return) . '&attach=invalid'), 302);
  }

  $staffId = function_exists('staff_id') ? (int)staff_id() : 0;
  if ($staffId < 1) {
    redirect_to($u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return) . '&attach=denied'), 302);
  }

  if (!defined('PRIVATE_PATH') || !is_string(PRIVATE_PATH) || PRIVATE_PATH === '') {
    redirect_to($u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return) . '&attach=error'), 302);
  }

  $fn = rtrim(PRIVATE_PATH, '/\\') . '/functions/page_attachments_external.php';
  if (!is_file($fn)) {
    redirect_to($u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return) . '&attach=error'), 302);
  }
  require_once $fn;

  if (!function_exists('mk_staff_add_external_page_attachment')) {
    redirect_to($u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return) . '&attach=error'), 302);
  }

  $rawUrl   = (string)($_POST['external_url'] ?? '');
  $cleanUrl = pf__normalize_external_url($rawUrl);

  $label = trim((string)($_POST['label'] ?? ''));
  $label = str_replace(["\r","\n"], '', $label);
  if (function_exists('mb_substr')) $label = (string)mb_substr($label, 0, 255, 'UTF-8');
  else $label = substr($label, 0, 255);

  try {
    $res = mk_staff_add_external_page_attachment($pdo, $id, $staffId, $cleanUrl, $label);

    if (!is_array($res) || empty($res['ok'])) {
      $err = strtolower(trim((string)($res['error'] ?? '')));
      if ($err !== '' && (
        str_contains($err, 'allow') ||
        str_contains($err, 'https') ||
        str_contains($err, 'host') ||
        str_contains($err, 'path') ||
        str_contains($err, 'url')
      )) {
        redirect_to($u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return) . '&attach=badurl'), 302);
      }
      redirect_to($u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return) . '&attach=error'), 302);
    }

    $fileId = 0;
    if (isset($res['id'])) $fileId = (int)$res['id'];
    elseif (isset($res['file_id'])) $fileId = (int)$res['file_id'];
    elseif (isset($res['attachment_id'])) $fileId = (int)$res['attachment_id'];

    if ($fileId <= 0) {
      try {
        $q = $pdo->prepare("
          SELECT id
          FROM page_files
          WHERE page_id = :pid
            AND is_external = 1
            AND external_url = :url
          ORDER BY id DESC
          LIMIT 1
        ");
        $q->execute([':pid' => $id, ':url' => $cleanUrl]);
        $fileId = (int)$q->fetchColumn();
      } catch (Throwable $e) {
        $fileId = 0;
      }
    }

    if ($fileId <= 0) {
      try { $fileId = (int)$pdo->lastInsertId(); } catch (Throwable $e) { $fileId = 0; }
    }

    if ($fileId > 0) {
      pf__backfill_external_attachment_meta($pdo, $fileId, $cleanUrl, $label);
    }

    redirect_to($u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return) . '&attach=saved'), 302);
  } catch (Throwable $e) {
    redirect_to($u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return) . '&attach=error'), 302);
  }
}

/* ---------------------------------------------------------
   Subjects dropdown
--------------------------------------------------------- */
$subjects = [];
if ($has_subject_id) {
  try {
    $sub_title_col = pf__column_exists($pdo, 'subjects', 'menu_name') ? 'menu_name'
                   : (pf__column_exists($pdo, 'subjects', 'name') ? 'name' : null);

    if ($sub_title_col) {
      $subjects = $pdo->query("SELECT id, {$sub_title_col} AS title, slug FROM subjects ORDER BY {$sub_title_col} ASC, id ASC")
                      ->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
      $subjects = $pdo->query("SELECT id, slug FROM subjects ORDER BY id ASC")
                      ->fetchAll(PDO::FETCH_ASSOC) ?: [];
      $subjects = array_map(static function(array $r): array {
        $sid = (int)($r['id'] ?? 0);
        return ['id' => $sid, 'title' => 'Subject #' . $sid, 'slug' => (string)($r['slug'] ?? '')];
      }, $subjects);
    }
  } catch (Throwable $e) { $subjects = []; }
}

/* Topic group suggestions */
$topic_groups = [];
if ($has_topic_group && $has_subject_id) {
  try {
    $sid = (int)($page['subject_id'] ?? 0);
    if ($sid > 0) {
      $gst = $pdo->prepare("
        SELECT DISTINCT topic_group
        FROM pages
        WHERE subject_id = :sid
          AND topic_group IS NOT NULL
          AND topic_group <> ''
        ORDER BY topic_group ASC
        LIMIT 200
      ");
      $gst->execute([':sid' => $sid]);
      $topic_groups = $gst->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
      $topic_groups = array_values(array_filter(array_map('strval', $topic_groups), static function(string $v): bool {
        return trim($v) !== '';
      }));
    }
  } catch (Throwable $e) { $topic_groups = []; }
}

/* Form state */
$form = [
  'subject_id'  => (string)($page['subject_id'] ?? ''),
  'title'       => (string)($page['title'] ?? ''),
  'slug'        => (string)($page['slug'] ?? ''),
  'topic_group' => (string)($page['topic_group'] ?? ''),
  'body'        => (string)($page['body'] ?? ''),
  'nav_order'   => (string)($page['nav_order'] ?? ''),
  'pub'         => (string)($page['pub'] ?? ''),
];

/* Normalize pub */
$pub_is_bool   = in_array($pub_col, ['is_public','visible'], true);
$pub_is_status = ($pub_col === 'status');

$form_public_checked = false;
if ($pub_is_bool) {
  $form_public_checked = ((int)$form['pub'] === 1);
} elseif ($pub_is_status) {
  $v = strtolower(trim($form['pub']));
  $form_public_checked = in_array($v, ['active','published','public'], true);
}

/* ---------------------------------------------------------
   Handle POST update (page fields only)
--------------------------------------------------------- */
if ($method === 'POST' && $action_post === 'update_page') {
  $token = (string)($_POST['csrf_token'] ?? '');
  if (!staff_csrf_verify($token)) {
    pf__flash_set('error', 'Security check failed (CSRF). Please retry.');
    redirect_to($u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return)), 302);
  }

  $form['subject_id']  = trim((string)($_POST['subject_id'] ?? ''));
  $form['title']       = trim((string)($_POST['title'] ?? ''));
  $form['slug']        = trim((string)($_POST['slug'] ?? ''));
  $form['topic_group'] = $has_topic_group ? pf__clean_topic_group((string)($_POST['topic_group'] ?? '')) : '';
  $form['body']        = (string)($_POST['body'] ?? '');
  $form['nav_order']   = trim((string)($_POST['nav_order'] ?? ''));

  $posted_public = ((string)($_POST['is_public'] ?? '0') === '1');

  if ($has_subject_id && $form['subject_id'] === '') $error = 'Please choose a subject.';
  elseif ($title_col && $form['title'] === '') $error = 'Please enter a title.';

  if ($error === '' && $has_slug) {
    if ($form['slug'] === '' && function_exists('mk_slugify')) {
      $form['slug'] = (string)mk_slugify($form['title'] !== '' ? $form['title'] : ('page-' . $id));
    }
    if ($form['slug'] === '') $error = 'Please enter a slug.';
    if ($error === '' && !preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', strtolower($form['slug']))) {
      $error = 'Slug must be lowercase and contain only letters, numbers, underscore or dash.';
    }
  }

  if ($error === '' && $has_topic_group && $form['topic_group'] !== '') {
    if (strlen($form['topic_group']) > 80) $error = 'Nav section (topic group) is too long (max 80 chars).';
  }

  /* Uniqueness */
  if ($error === '' && $has_subject_id && $has_slug) {
    $sid  = (int)$form['subject_id'];
    $slug = strtolower((string)$form['slug']);

    if ($has_topic_group) {
      $tgVal = ($form['topic_group'] === '') ? null : (string)$form['topic_group'];

      $chk = $pdo->prepare("
        SELECT id
        FROM pages
        WHERE subject_id = :sid
          AND slug = :slug
          AND (topic_group <=> :tg)
          AND id <> :id
        LIMIT 1
      ");
      $chk->bindValue(':sid', $sid, PDO::PARAM_INT);
      $chk->bindValue(':slug', $slug, PDO::PARAM_STR);
      $chk->bindValue(':id', $id, PDO::PARAM_INT);
      if ($tgVal === null) $chk->bindValue(':tg', null, PDO::PARAM_NULL);
      else $chk->bindValue(':tg', $tgVal, PDO::PARAM_STR);
      $chk->execute();

      if ((int)$chk->fetchColumn() > 0) $error = 'Another page already uses this Subject + Nav section + Slug.';
    } else {
      $chk = $pdo->prepare("
        SELECT id
        FROM pages
        WHERE subject_id = :sid
          AND slug = :slug
          AND id <> :id
        LIMIT 1
      ");
      $chk->execute([':sid' => $sid, ':slug' => $slug, ':id' => $id]);
      if ((int)$chk->fetchColumn() > 0) $error = 'Another page already uses this Subject + Slug.';
    }
  }

  if ($error === '') {
    try {
      $sets = [];
      $bind = [':id' => $id];

      if ($has_subject_id) { $sets[] = 'subject_id = :sid'; $bind[':sid'] = (int)$form['subject_id']; }
      if ($title_col)      { $sets[] = "{$title_col} = :title"; $bind[':title'] = $form['title']; }
      if ($has_slug)       { $sets[] = "slug = :slug"; $bind[':slug'] = strtolower($form['slug']); }
      if ($has_topic_group) {
        if ($form['topic_group'] === '') $sets[] = "topic_group = NULL";
        else { $sets[] = "topic_group = :tg"; $bind[':tg'] = $form['topic_group']; }
      }
      if ($body_col) {
        $sets[] = "{$body_col} = :body";
        $bind[':body'] = $form['body'];
      }
      if ($order_col) {
        if ($form['nav_order'] === '') $sets[] = "{$order_col} = NULL";
        else { $sets[] = "{$order_col} = :ord"; $bind[':ord'] = (int)$form['nav_order']; }
      }
      if ($pub_col) {
        if ($pub_is_bool) { $sets[] = "{$pub_col} = :pub"; $bind[':pub'] = $posted_public ? 1 : 0; }
        elseif ($pub_is_status) { $sets[] = "status = :status"; $bind[':status'] = $posted_public ? 'active' : 'draft'; }
      }

      if (!$sets) throw new RuntimeException('No updatable columns detected for pages table.');

      $sql = "UPDATE pages SET " . implode(', ', $sets) . " WHERE id = :id LIMIT 1";
      $st2 = $pdo->prepare($sql);

      foreach ($bind as $k => $v) {
        if ($v === null) $st2->bindValue($k, null, PDO::PARAM_NULL);
        elseif (is_int($v)) $st2->bindValue($k, $v, PDO::PARAM_INT);
        else $st2->bindValue($k, (string)$v, PDO::PARAM_STR);
      }

      $st2->execute();

      pf__flash_set('notice', 'Page updated.');
      redirect_to($u($return), 302);
    } catch (Throwable $e) {
      $error = 'Update failed: ' . $e->getMessage();
    }
  }
}

/* ---------------------------------------------------------
   Attachments list (page_files)
--------------------------------------------------------- */
$attachments = [];
if ($pageFilesExists) {
  try {
    $sqlA = "
      SELECT
        id, page_id, is_external,
        external_url, external_host,
        stored_path, original_name, stored_name, file_path, mime_type,
        kind, source_key, source_label, host, canonical_url, title, authors, pub_year, doi, isbn, lang,
        file_size, sort_order, created_at
      FROM page_files
      WHERE page_id = :pid
      ORDER BY
        sort_order IS NULL, sort_order ASC, id DESC
    ";
    $stA = $pdo->prepare($sqlA);
    $stA->execute([':pid' => $id]);
    $attachments = $stA->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (Throwable $e) {
    $attachments = [];
  }
}

/* Header */
$active_nav = 'pgs';
$page_title = 'Edit Page • Staff';
require_once APP_ROOT . '/app/mkomigbo/private/shared/staff_header.php';

/* Define one CSRF field HTML for reuse everywhere */
$csrf_html = staff_csrf_field();

/* URLs */
$action_url = $u('/staff/subjects/pgs/edit.php') . '?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return);
$show_url   = $u('/staff/subjects/pgs/show.php') . '?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return);

/* Handlers */
$upload_action   = $u('/staff/pages/attachments_upload.php');
$delete_action   = $u('/staff/pages/attachments_delete.php');
$download_action = $u('/staff/page-files/download.php');
$open_action     = $u('/staff/page-files/open.php');

/* Signed URL helper (optional, loaded once) */
$mk_signed_open_url = null;
try {
  if (defined('PRIVATE_PATH') && is_string(PRIVATE_PATH) && PRIVATE_PATH !== '') {
    $sf = rtrim(PRIVATE_PATH, '/\\') . '/functions/staff_signed_open.php';
    if (is_file($sf)) require_once $sf;
    if (function_exists('mk_staff_signed_open_url')) {
      $mk_signed_open_url = static fn(int $fid, int $pid): string => mk_staff_signed_open_url($fid, $pid, 900);
    }
  }
} catch (Throwable $e) {}

/* Current URI for return */
$current_uri = (string)($_SERVER['REQUEST_URI'] ?? ('/staff/subjects/pgs/edit.php?id=' . $id));
$current_uri = staff_safe_return_url($current_uri, '/staff/subjects/pgs/edit.php?id=' . $id);

/* Allowlist preview (optional) */
$allow_cfg = [];
$allow_cfg_path = (defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '') . '/private/config/external_attachments_allowlist.php';
if ($allow_cfg_path !== '' && is_file($allow_cfg_path)) {
  $tmp = require $allow_cfg_path;
  $allow_cfg = is_array($tmp) ? $tmp : [];
}
$allow_keys = array_keys($allow_cfg);
sort($allow_keys);

?>
<div class="container">

  <div class="hero">
    <div class="hero__row">
      <div>
        <h1 class="hero__title">Edit Page</h1>
        <p class="hero__sub"><?php echo h($form['title'] !== '' ? $form['title'] : ('Page #' . $id)); ?></p>
      </div>
      <div class="hero__actions">
        <a class="btn btn--ghost" href="<?php echo h($u($return)); ?>">← Back</a>
        <a class="btn" href="<?php echo h($show_url); ?>">View</a>
      </div>
    </div>
  </div>

  <?php if ($notice !== ''): ?><div class="alert alert--success"><?php echo h($notice); ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert alert--danger"><?php echo h($error); ?></div><?php endif; ?>
  <?php if ($attachMsg !== ''): ?><div class="alert alert--info"><?php echo h($attachMsg); ?></div><?php endif; ?>

  <div class="card">
    <div class="card__body">
      <form method="post" action="<?php echo h($action_url); ?>" class="stack">
        <?php echo $csrf_html; ?>
        <input type="hidden" name="action" value="update_page">
        <input type="hidden" name="return" value="<?php echo h($return); ?>">

        <?php if ($has_subject_id): ?>
          <div class="field">
            <label class="label" for="subject_id">Subject</label>
            <select class="input" id="subject_id" name="subject_id" required>
              <option value="">— Choose —</option>
              <?php foreach ($subjects as $s): ?>
                <?php
                  $sid = (int)($s['id'] ?? 0);
                  $stitle = trim((string)($s['title'] ?? '')) ?: ('Subject #' . $sid);
                ?>
                <option value="<?php echo h((string)$sid); ?>" <?php echo ((string)$sid === $form['subject_id']) ? 'selected' : ''; ?>>
                  <?php echo h($stitle); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($title_col): ?>
          <div class="field">
            <label class="label" for="title">Title</label>
            <input class="input" id="title" name="title" value="<?php echo h($form['title']); ?>" required>
          </div>
        <?php endif; ?>

        <?php if ($has_slug): ?>
          <div class="field">
            <label class="label" for="slug">Slug</label>
            <input class="input mono" id="slug" name="slug" value="<?php echo h($form['slug']); ?>" placeholder="auto if blank">
          </div>
        <?php endif; ?>

        <?php if ($has_topic_group): ?>
          <div class="field">
            <label class="label" for="topic_group">Nav section (topic group)</label>
            <input class="input" id="topic_group" name="topic_group" maxlength="80"
                   value="<?php echo h($form['topic_group']); ?>"
                   placeholder="e.g. Background, Key figures, Timeline"
                   list="topicGroupList">
            <div class="muted" style="margin-top:6px;">Used to group links on the subject landing page.</div>
            <?php if (!empty($topic_groups)): ?>
              <datalist id="topicGroupList">
                <?php foreach ($topic_groups as $tg): ?>
                  <option value="<?php echo h((string)$tg); ?>"></option>
                <?php endforeach; ?>
              </datalist>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($order_col): ?>
          <div class="field">
            <label class="label" for="nav_order"><?php echo h($order_col === 'position' ? 'Position' : 'Nav order'); ?></label>
            <input class="input mono" id="nav_order" name="nav_order" type="number" value="<?php echo h($form['nav_order']); ?>" placeholder="10, 20, 30...">
          </div>
        <?php endif; ?>

        <?php if ($pub_col): ?>
          <div class="field">
            <label class="check">
              <input type="checkbox" name="is_public" value="1" <?php echo $form_public_checked ? 'checked' : ''; ?>>
              <span>Public (published)</span>
            </label>
          </div>
        <?php endif; ?>

        <?php if ($body_col): ?>
          <div class="field">
            <label class="label" for="body"><?php echo h($body_col === 'body_html' ? 'Body (HTML)' : 'Body'); ?></label>
            <textarea class="input" id="body" name="body" rows="12"><?php echo h($form['body']); ?></textarea>
          </div>
        <?php endif; ?>

        <div class="row row--gap">
          <button class="btn btn--primary" type="submit">Save changes</button>
          <a class="btn btn--ghost" href="<?php echo h($u($return)); ?>">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Attachments -->
  <div class="card" style="margin-top:14px;">
    <div class="card__body">

      <div class="row row--gap" style="align-items:center; justify-content:space-between;">
        <h2 style="margin:0;">Attachments</h2>
      </div>

      <?php if (!$pageFilesExists): ?>
        <div class="alert alert--warning" style="margin-top:10px;">
          The <code>page_files</code> table is missing, so attachments are disabled.
        </div>
      <?php else: ?>

        <!-- Upload form -->
        <form method="post" action="<?php echo h($upload_action); ?>" enctype="multipart/form-data" class="stack" style="margin-top:12px;">
          <?php echo $csrf_html; ?>
          <input type="hidden" name="page_id" value="<?php echo (int)$id; ?>">
          <input type="hidden" name="return" value="<?php echo h($current_uri); ?>">

          <div class="field">
            <label class="label" for="attachments">Add attachments</label>
            <input class="input" id="attachments" type="file" name="attachments[]" multiple>
            <div class="muted" style="margin-top:6px;">Allowed types and max size are enforced by the upload handler.</div>
          </div>

          <div class="row row--gap">
            <button class="btn btn--primary" type="submit">Upload</button>
          </div>
        </form>

        <!-- External link form -->
        <form method="post" action="<?php echo h($action_url); ?>" class="stack" style="margin-top:12px;">
          <?php echo $csrf_html; ?>
          <input type="hidden" name="action" value="add_external">
          <input type="hidden" name="page_id" value="<?php echo (int)$id; ?>">
          <input type="hidden" name="return" value="<?php echo h($current_uri); ?>">

          <div class="field">
            <label class="label" for="external_url">Add external link (allowlisted HTTPS only)</label>
            <input class="input" id="external_url" name="external_url" type="text"
                   inputmode="url" autocomplete="off" spellcheck="false"
                   placeholder="https://en.wikipedia.org/wiki/Igbo_people"
                   required>
            <div class="muted" style="margin-top:6px;">
              You can paste a URL — the server will clean it and enforce allowlist rules.
            </div>
          </div>

          <div class="field">
            <label class="label" for="label">Label (optional)</label>
            <input class="input" id="label" name="label" maxlength="255" placeholder="e.g. Source PDF, Documentary video">
          </div>

          <div class="row row--gap">
            <button class="btn btn--ghost" type="submit">Add external link</button>
          </div>
        </form>

        <!-- Tiny diagnostics -->
        <details style="margin-top:10px;">
          <summary class="muted" style="cursor:pointer;">Attachment diagnostics</summary>
          <div class="muted" style="margin-top:8px; font-size:.92rem;">
            <div><strong>Allowlist file:</strong> <code><?php echo h($allow_cfg_path); ?></code></div>
            <div style="margin-top:6px;"><strong>Allowlisted roots:</strong>
              <?php if (!$allow_keys): ?>
                <em>none loaded</em>
              <?php else: ?>
                <code><?php echo h(implode(', ', $allow_keys)); ?></code>
              <?php endif; ?>
            </div>

            <div style="margin-top:10px;">
              <strong>Normalizer preview:</strong>
              <div class="mono" style="margin-top:4px; word-break:break-word;">
                <div>input: <code id="pfDiagIn">—</code></div>
                <div>clean: <code id="pfDiagClean">—</code></div>
              </div>
            </div>
          </div>
        </details>

        <script>
        (function(){
          var el = document.getElementById('external_url');
          var outIn = document.getElementById('pfDiagIn');
          var outCl = document.getElementById('pfDiagClean');
          if(!el || !outIn || !outCl) return;

          function clean(v){
            v = (v || '').replace(/[\x00-\x1F\x7F]/g,'').trim();
            v = v.split(/\s+/)[0] || '';
            v = v.replace(/[.,;:)\]}'"]+$/,'');
            if(v.startsWith('<') && v.endsWith('>')) v = v.slice(1,-1).trim();
            if(v.startsWith('//')) v = 'https:' + v;
            if(v && !/^[a-zA-Z][a-zA-Z0-9+\-.]*:\/\//.test(v) && v[0] !== '/' && v[0] !== '\\') v = 'https://' + v;
            return v;
          }
          function update(){
            outIn.textContent = el.value || '—';
            outCl.textContent = clean(el.value) || '—';
          }
          el.addEventListener('input', update);
          el.addEventListener('change', update);
          update();
        })();
        </script>

        <?php if (empty($attachments)): ?>
          <p class="muted" style="margin-top:12px;"><em>No attachments yet.</em></p>
        <?php else: ?>
          <div style="margin-top:14px; overflow:auto;">
            <table class="table" style="width:100%; min-width:980px;">
              <thead>
                <tr>
                  <th style="text-align:left;">Name</th>
                  <th style="text-align:left;">Type</th>
                  <th style="text-align:left;">Meta</th>
                  <th style="text-align:right;">Size</th>
                  <th style="text-align:left;">Added</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($attachments as $a): ?>
                  <?php
                    $aid = (int)($a['id'] ?? 0);

                    $isExternal = ((int)($a['is_external'] ?? 0) === 1) || (!empty($a['external_url']));

                    $extUrl  = $isExternal ? trim((string)($a['external_url'] ?? '')) : '';
                    $extHost = $isExternal ? trim((string)($a['external_host'] ?? '')) : '';

                    $name = trim((string)($a['original_name'] ?? ''));
                    if ($name === '' && $isExternal && $extHost !== '') $name = 'External link (' . $extHost . ')';
                    if ($name === '') $name = 'Attachment #' . $aid;

                    $mime  = trim((string)($a['mime_type'] ?? ''));
                    $bytes = (int)($a['file_size'] ?? 0);
                    $when  = trim((string)($a['created_at'] ?? ''));

                    $kind = trim((string)($a['kind'] ?? ''));
                    $host = trim((string)($a['host'] ?? ''));
                    $canon = trim((string)($a['canonical_url'] ?? ''));
                    $skey = trim((string)($a['source_key'] ?? ''));
                    $slabel = trim((string)($a['source_label'] ?? ''));
                    $title = trim((string)($a['title'] ?? ''));

                    $typeLabel = $isExternal
                      ? ('External' . ($extHost !== '' ? ' • ' . $extHost : ''))
                      : ($mime !== '' ? $mime : 'Local');

                    $sizeLabel = '—';
                    if (!$isExternal && $bytes > 0) {
                      $kb = $bytes / 1024;
                      $mb = $kb / 1024;
                      $sizeLabel = ($mb >= 1) ? (number_format($mb, 2) . ' MB') : (number_format($kb, 0) . ' KB');
                    }

                    $detail = '';
                    if ($isExternal && $extUrl !== '') $detail = $extUrl;
                    if (!$isExternal && !empty($a['file_path'])) $detail = (string)$a['file_path'];

                    $metaBits = [];
                    if ($kind !== '') $metaBits[] = 'kind: ' . $kind;
                    if ($host !== '') $metaBits[] = 'host: ' . $host;
                    if ($skey !== '') $metaBits[] = 'key: ' . $skey;
                    if ($slabel !== '' && $slabel !== $name) $metaBits[] = 'label: ' . $slabel;
                    if ($title !== '' && $title !== $name && $title !== $slabel) $metaBits[] = 'title: ' . $title;
                    if ($canon !== '' && $canon !== $extUrl) $metaBits[] = 'canon: ' . $canon;

                    $metaLine = $metaBits ? implode(' • ', $metaBits) : '—';

                    $signedCopy = is_callable($mk_signed_open_url) ? (string)$mk_signed_open_url($aid, (int)$id) : '';
                  ?>
                  <tr>
                    <td>
                      <div style="display:flex; gap:8px; align-items:center;">
                        <span><?php echo h($name); ?></span>
                        <?php if ($isExternal): ?>
                          <span class="muted">(external)</span>
                        <?php endif; ?>
                      </div>

                      <?php if ($detail !== ''): ?>
                        <div class="muted" style="margin-top:4px; font-size:.9rem; word-break:break-word;">
                          <?php echo h($detail); ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td><?php echo h($typeLabel); ?></td>
                    <td>
                      <div class="muted" style="font-size:.9rem; word-break:break-word;">
                        <?php echo h($metaLine); ?>
                      </div>
                    </td>
                    <td style="text-align:right;"><?php echo h($sizeLabel); ?></td>
                    <td><?php echo h($when !== '' ? $when : '—'); ?></td>
                    <td style="text-align:right; white-space:nowrap;">

                      <?php if ($isExternal): ?>
                        <form method="post" action="<?php echo h($open_action); ?>" target="_blank" style="display:inline;">
                          <?php echo $csrf_html; ?>
                          <input type="hidden" name="file_id" value="<?php echo (int)$aid; ?>">
                          <input type="hidden" name="page_id" value="<?php echo (int)$id; ?>">
                          <button class="btn" type="submit">Open</button>
                        </form>
                      <?php else: ?>
                        <form method="post" action="<?php echo h($open_action); ?>" target="_blank" style="display:inline;">
                          <?php echo $csrf_html; ?>
                          <input type="hidden" name="file_id" value="<?php echo (int)$aid; ?>">
                          <input type="hidden" name="page_id" value="<?php echo (int)$id; ?>">
                          <button class="btn" type="submit">Open</button>
                        </form>

                        <form method="post" action="<?php echo h($download_action); ?>" target="_blank" style="display:inline; margin-left:6px;">
                          <?php echo $csrf_html; ?>
                          <input type="hidden" name="file_id" value="<?php echo (int)$aid; ?>">
                          <input type="hidden" name="page_id" value="<?php echo (int)$id; ?>">
                          <button class="btn btn--ghost" type="submit">Download</button>
                        </form>
                      <?php endif; ?>

                      <?php if ($signedCopy !== ''): ?>
                        <button
                          type="button"
                          class="btn btn--secondary"
                          data-copy="<?php echo h($signedCopy); ?>"
                          data-copied-text="Copied"
                          data-copy-reset-ms="1200"
                          style="margin-left:6px;"
                        >Copy signed link (15m)</button>
                      <?php endif; ?>

                      <form method="post" action="<?php echo h($delete_action); ?>" style="display:inline; margin-left:6px;">
                        <?php echo $csrf_html; ?>
                        <input type="hidden" name="id" value="<?php echo (int)$aid; ?>">
                        <input type="hidden" name="page_id" value="<?php echo (int)$id; ?>">
                        <input type="hidden" name="return" value="<?php echo h($current_uri); ?>">
                        <button class="btn btn--ghost" type="submit" onclick="return confirm('Delete this attachment?');">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>

</div>

<?php
require_once APP_ROOT . '/app/mkomigbo/private/shared/staff_footer.php';
