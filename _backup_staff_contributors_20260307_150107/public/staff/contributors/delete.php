<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

mk_require_staff_login();

/**
 * /public/staff/contributors/delete.php
 * Staff: Delete contributor (confirm + POST)
 *
 * - Schema-tolerant contributor label lookup
 * - CSRF protected
 * - No arrow functions
 */

if (function_exists('require_staff_login')) { require_staff_login(); }

/* ---------------------------------------------------------
   Helpers
--------------------------------------------------------- */
if (!function_exists('h')) {
  function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void {
    $location = str_replace(["\r", "\n"], '', $location);
    header('Location: ' . $location, true, 302);
    exit;
  }
}

/* CSRF helpers */
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
  }
}
if (!function_exists('csrf_field')) {
  function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
  }
}
if (!function_exists('csrf_ok')) {
  function csrf_ok(string $token): bool {
    if (function_exists('csrf_token_is_valid') && function_exists('csrf_token_is_recent')) {
      return csrf_token_is_valid($token) && csrf_token_is_recent($token);
    }
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    $sess = (string)($_SESSION['csrf_token'] ?? '');
    return ($token !== '' && $sess !== '' && hash_equals($sess, $token));
  }
}

/* Flash */
if (!function_exists('pf__flash_set')) {
  function pf__flash_set(string $key, string $msg): void {
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) $_SESSION['flash'] = [];
    $_SESSION['flash'][$key] = $msg;
  }
}
if (!function_exists('pf__flash_get')) {
  function pf__flash_get(string $key): string {
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    $msg = '';
    if (isset($_SESSION['flash']) && is_array($_SESSION['flash']) && array_key_exists($key, $_SESSION['flash'])) {
      $msg = (string)$_SESSION['flash'][$key];
      unset($_SESSION['flash'][$key]);
    }
    return $msg;
  }
}

/* Safe return */
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
if (!function_exists('pf__u')) {
  function pf__u(string $path): string {
    return function_exists('url_for') ? url_for($path) : $path;
  }
}

/* Schema */
if (!function_exists('pf__table_exists')) {
  function pf__table_exists(PDO $pdo, string $table): bool {
    $st = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1");
    $st->execute([$table]);
    return (bool)$st->fetchColumn();
  }
}
if (!function_exists('pf__column_exists')) {
  function pf__column_exists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$table, $column]);
    return ((int)$st->fetchColumn() > 0);
  }
}

/* ---------------------------------------------------------
   Method guard
--------------------------------------------------------- */
$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'GET' && $method !== 'POST') {
  http_response_code(405);
  header('Allow: GET, POST');
  echo "Method Not Allowed";
  exit;
}

/* ---------------------------------------------------------
   DB
--------------------------------------------------------- */
$pdo = function_exists('staff_pdo') ? staff_pdo() : (function_exists('db') ? db() : null);
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

/* Params */
$default_return = '/staff/contributors/index.php';
$return = pf__safe_return_url((string)($_REQUEST['return'] ?? $default_return), $default_return);

$id = (int)($_REQUEST['id'] ?? 0);
if ($id <= 0) redirect_to(pf__u($return));

$notice = pf__flash_get('notice');
$error  = pf__flash_get('error');

/* ---------------------------------------------------------
   Load contributor label (schema-tolerant)
--------------------------------------------------------- */
$name = 'Contributor #' . $id;
$warn = '';

try {
  if (!pf__table_exists($pdo, 'contributors')) {
    $warn = 'Table "contributors" not found.';
  } else {
    $label_cols = [];
    $candidates = ['display_name','name','username','email','slug'];
    foreach ($candidates as $c) {
      if (pf__column_exists($pdo, 'contributors', $c)) $label_cols[] = $c;
    }

    $select = ['id'];
    foreach ($label_cols as $c) { $select[] = '`' . str_replace('`', '', $c) . '`'; }

    $sql = "SELECT " . implode(', ', $select) . " FROM contributors WHERE id = ? LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      pf__flash_set('error', 'Contributor not found.');
      redirect_to(pf__u($return));
    }

    foreach ($candidates as $c) {
      if (isset($row[$c])) {
        $tmp = trim((string)$row[$c]);
        if ($tmp !== '') { $name = $tmp; break; }
      }
    }
  }
} catch (Throwable $e) {
  // If even label lookup fails, still allow delete attempt (will handle rowCount)
}

/* ---------------------------------------------------------
   POST: delete
--------------------------------------------------------- */
if ($method === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');
  if (!csrf_ok($token)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Invalid CSRF token.";
    exit;
  }

  try {
    $st = $pdo->prepare("DELETE FROM contributors WHERE id = ? LIMIT 1");
    $st->execute([$id]);

    if ($st->rowCount() < 1) {
      pf__flash_set('error', 'Contributor not found or already deleted.');
      redirect_to(pf__u($return));
    }

    pf__flash_set('notice', 'Contributor deleted.');
    redirect_to(pf__u($return));
  } catch (Throwable $e) {
    pf__flash_set('error', 'Delete failed.');
    redirect_to(pf__u($return));
  }
}

/* ---------------------------------------------------------
   Header
--------------------------------------------------------- */
$active_nav = 'contributors';
$page_title = 'Delete Contributor • Staff';
$page_desc  = 'Confirm contributor deletion.';

$staff_subnav = [
  ['label' => 'Dashboard',    'href' => pf__u('/staff/'),              'active' => false],
  ['label' => 'Contributors', 'href' => pf__u('/staff/contributors/'), 'active' => true],
  ['label' => 'Public',       'href' => pf__u('/contributors/'),       'active' => false],
];

require_once APP_ROOT . '/private/shared/staff_header.php';

$back_url = pf__u($return);

?>
<div class="container">

  <div class="hero">
    <div class="hero__row">
      <div>
        <h1 class="hero__title">Delete Contributor</h1>
        <p class="hero__sub">This action cannot be undone.</p>
      </div>
      <div class="hero__actions">
        <a class="btn btn--ghost" href="<?php echo h($back_url); ?>">← Back</a>
      </div>
    </div>
  </div>

  <?php if ($notice !== ''): ?>
    <div class="alert alert--success"><?php echo h($notice); ?></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="alert alert--danger"><?php echo h($error); ?></div>
  <?php endif; ?>
  <?php if ($warn !== ''): ?>
    <div class="alert alert--warning"><?php echo h($warn); ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card__body">
      <p>Are you sure you want to delete:</p>
      <p class="strong"><?php echo h($name); ?></p>

      <form method="post" action="<?php echo h(pf__u('/staff/contributors/delete.php')); ?>" class="row row--gap" style="margin-top:14px;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo h((string)$id); ?>">
        <input type="hidden" name="return" value="<?php echo h($return); ?>">

        <button class="btn btn--danger" type="submit" onclick="return confirm('Delete this contributor permanently?');">
          Yes, delete
        </button>
        <a class="btn btn--ghost" href="<?php echo h($back_url); ?>">Cancel</a>
      </form>
    </div>
  </div>

</div>

<?php require APP_ROOT . '/private/shared/staff_footer.php'; ?>
