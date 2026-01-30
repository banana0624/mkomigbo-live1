<?php
declare(strict_types=1);

/**
 * /public/staff/subjects/pgs/delete.php
 * Staff: Delete a page (confirm + POST), schema-tolerant.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../../_init.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void {
    $location = str_replace(["\r", "\n"], '', $location);
    header('Location: ' . $location, true, 302);
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
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    $sess = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sess) || $sess === '' || $token === '') return false;
    return hash_equals($sess, $token);
  }
}
if (!function_exists('staff_csrf_field')) {
  function staff_csrf_field(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
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
if (!function_exists('pf__column_exists')) {
  function pf__column_exists(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) return (bool)$cache[$key];

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
  }
}
if (!function_exists('pf__table_exists')) {
  function pf__table_exists(PDO $pdo, string $table): bool {
    static $cache = [];
    $key = strtolower($table);
    if (array_key_exists($key, $cache)) return (bool)$cache[$key];

    $st = $pdo->prepare("
      SELECT 1
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
      LIMIT 1
    ");
    $st->execute([$table]);
    $cache[$key] = (bool)$st->fetchColumn();
    return (bool)$cache[$key];
  }
}

/* Auth */
if (function_exists('require_staff')) {
  require_staff();
} elseif (function_exists('require_staff_login')) {
  require_staff_login();
} elseif (function_exists('mk_require_staff_login')) {
  mk_require_staff_login();
}

/* DB */
$pdo = staff_pdo();
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

/* Inputs */
$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
if ($id <= 0) redirect_to('/staff/subjects/pgs/index.php');

$return = staff_safe_return_url((string)($_GET['return'] ?? ($_POST['return'] ?? '')), '/staff/subjects/pgs/index.php');

$notice = pf__flash_get('notice');
$error  = pf__flash_get('error');

/* Page title (tolerant) */
$has_title = pf__column_exists($pdo, 'pages', 'title');
$has_menu  = pf__column_exists($pdo, 'pages', 'menu_name');
$has_name  = pf__column_exists($pdo, 'pages', 'name');

$cols = ['id'];
if ($has_title) $cols[] = 'title';
if ($has_menu)  $cols[] = 'menu_name';
if ($has_name)  $cols[] = 'name';

$st = $pdo->prepare("SELECT " . implode(', ', $cols) . " FROM pages WHERE id = :id LIMIT 1");
$st->execute([':id' => $id]);
$page = $st->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$page) {
  pf__flash_set('error', 'Page not found.');
  redirect_to($return);
}

$label = '';
foreach (['title','menu_name','name'] as $k) {
  if (isset($page[$k]) && is_string($page[$k]) && trim($page[$k]) !== '') { $label = trim($page[$k]); break; }
}
if ($label === '') $label = 'Page #' . (string)$id;

/* POST delete */
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');
  if (!staff_csrf_verify($token)) {
    pf__flash_set('error', 'Security check failed (CSRF). Please retry.');
    redirect_to('/staff/subjects/pgs/delete.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return));
  }

  try {
    $pdo->beginTransaction();

    // Best-effort delete attachments rows (page_files)
    if (pf__table_exists($pdo, 'page_files') && pf__column_exists($pdo, 'page_files', 'page_id')) {
      $pdo->prepare("DELETE FROM page_files WHERE page_id = :pid")->execute([':pid' => $id]);
    }

    $pdo->prepare("DELETE FROM pages WHERE id = :id LIMIT 1")->execute([':id' => $id]);

    $pdo->commit();

    pf__flash_set('notice', 'Page deleted.');
    redirect_to($return);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $msg = $e->getMessage();
    pf__flash_set('error', 'Delete failed: ' . $msg);
    redirect_to('/staff/subjects/pgs/delete.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return));
  }
}

/* Render */
$active_nav = 'pgs';
$page_title = 'Delete Page • Staff';
require_once APP_ROOT . '/private/shared/staff_header.php';

$action = (function_exists('url_for') ? (string)url_for('/staff/subjects/pgs/delete.php') : '/staff/subjects/pgs/delete.php');

?>
<div class="container" style="padding:24px 0;">

  <section class="hero">
    <div class="hero-bar"></div>
    <div class="hero-inner">
      <h1>Delete Page</h1>
      <p class="muted" style="margin:6px 0 0;">
        You are about to delete <span class="pill">ID <?php echo h((string)$id); ?></span>
        <span class="pill"><?php echo h($label); ?></span>
      </p>

      <div class="actions" style="margin-top:14px;">
        <a class="btn" href="<?php echo h($return); ?>">← Back</a>
      </div>
    </div>
  </section>

  <?php if ($notice !== ''): ?><div class="notice success" style="margin-top:14px;"><strong><?php echo h($notice); ?></strong></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="notice error" style="margin-top:14px;"><strong><?php echo h($error); ?></strong></div><?php endif; ?>

  <section class="card form-card" style="margin-top:14px;">
    <form method="post" action="<?php echo h($action); ?>">
      <?php echo staff_csrf_field(); ?>
      <input type="hidden" name="id" value="<?php echo h((string)$id); ?>">
      <input type="hidden" name="return" value="<?php echo h($return); ?>">

      <p class="muted" style="margin:0 0 12px;">
        This action is permanent. The page and its attachment records will be removed.
      </p>

      <div class="actions">
        <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this page permanently?');">Yes, delete</button>
        <a class="btn" href="<?php echo h($return); ?>">Cancel</a>
      </div>
    </form>
  </section>

</div>

<?php require_once APP_ROOT . '/private/shared/staff_footer.php'; ?>
