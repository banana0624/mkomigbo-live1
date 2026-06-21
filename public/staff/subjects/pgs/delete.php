<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';
auth_require_role('staff');

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('url_for')) {
  function url_for(string $path): string { return '/' . ltrim($path, '/'); }
}
if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void {
    $location = str_replace(["\r","\n"], '', trim($location));
    if ($location === '') $location = '/staff/subjects/pgs/index.php';
    header('Location: ' . $location, true, 302);
    exit;
  }
}
if (!function_exists('pf__u')) {
  function pf__u(string $path): string { return function_exists('url_for') ? (string)url_for($path) : $path; }
}
if (!function_exists('pf__flash_set')) {
  function pf__flash_set(string $key, string $msg): void {
    mk_staff_session_start();
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
if (!function_exists('pf__safe_return_url')) {
  function pf__safe_return_url(string $raw, string $default): string {
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
if (!function_exists('pf__csrf_verify')) {
  function pf__csrf_verify(string $token): bool {
    mk_staff_session_start();
    $sess = $_SESSION['csrf_token'] ?? '';
    return is_string($sess) && $sess !== '' && $token !== '' && hash_equals($sess, $token);
  }
}
if (!function_exists('pf__csrf_field')) {
  function pf__csrf_field(): string {
    mk_staff_session_start();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . h((string)$_SESSION['csrf_token']) . '">';
  }
}
if (!function_exists('pf__pdo')) {
  function pf__pdo(): ?PDO {
    if (function_exists('staff_pdo')) {
      $pdo = staff_pdo();
      if ($pdo instanceof PDO) return $pdo;
    }
    if (function_exists('db')) {
      $pdo = db();
      if ($pdo instanceof PDO) return $pdo;
    }
    return null;
  }
}
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
    } catch (Throwable $e) {
      $cache[$key] = false;
    }
    return (bool)$cache[$key];
  }
}
if (!function_exists('pf__table_exists')) {
  function pf__table_exists(PDO $pdo, string $table): bool {
    static $cache = [];
    $key = strtolower($table);
    if (array_key_exists($key, $cache)) return (bool)$cache[$key];
    try {
      $st = $pdo->prepare("
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
        LIMIT 1
      ");
      $st->execute([$table]);
      $cache[$key] = (bool)$st->fetchColumn();
    } catch (Throwable $e) {
      $cache[$key] = false;
    }
    return (bool)$cache[$key];
  }
}
if (!function_exists('pf__page_title_of')) {
  function pf__page_title_of(array $row): string {
    foreach (['title','menu_name','name'] as $k) {
      if (isset($row[$k]) && trim((string)$row[$k]) !== '') return trim((string)$row[$k]);
    }
    return 'Page #' . (string)($row['id'] ?? '0');
  }
}

$pdo = pf__pdo();
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$return = pf__safe_return_url((string)($_GET['return'] ?? ($_POST['return'] ?? '')), '/staff/subjects/pgs/index.php');
if ($id <= 0) redirect_to(pf__u($return));

$notice = pf__flash_get('notice');
$error  = pf__flash_get('error');

$cols = ['id'];
if (pf__column_exists($pdo, 'pages', 'title')) $cols[] = 'title';
if (pf__column_exists($pdo, 'pages', 'menu_name')) $cols[] = 'menu_name';
if (pf__column_exists($pdo, 'pages', 'name')) $cols[] = 'name';

$st = $pdo->prepare("SELECT " . implode(', ', $cols) . " FROM pages WHERE id = :id LIMIT 1");
$st->execute([':id' => $id]);
$page = $st->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$page) {
  pf__flash_set('error', 'Page not found.');
  redirect_to(pf__u($return));
}

$label = pf__page_title_of($page);

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
  if (!pf__csrf_verify((string)($_POST['csrf_token'] ?? ''))) {
    pf__flash_set('error', 'Security check failed (CSRF). Please retry.');
    redirect_to(pf__u('/staff/subjects/pgs/delete.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return)));
  }

  try {
    $pdo->beginTransaction();

    if (pf__table_exists($pdo, 'page_files') && pf__column_exists($pdo, 'page_files', 'page_id')) {
      $pdo->prepare("DELETE FROM page_files WHERE page_id = :pid")->execute([':pid' => $id]);
    }

    $pdo->prepare("DELETE FROM pages WHERE id = :id LIMIT 1")->execute([':id' => $id]);

    $pdo->commit();
    pf__flash_set('notice', 'Page deleted.');
    redirect_to(pf__u($return));
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    pf__flash_set('error', 'Delete failed: ' . $e->getMessage());
    redirect_to(pf__u('/staff/subjects/pgs/delete.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return)));
  }
}

$page_title = 'Delete Page • Staff';
$active_nav = 'pgs';
require_once APP_ROOT . '/private/shared/staff_header.php';
?>
<div class="container" style="padding:24px 0;">
  <section class="hero">
    <div class="hero-bar"></div>
    <div class="hero-inner">
      <h1>Delete Page</h1>
      <p class="muted" style="margin:6px 0 0;">
        You are about to delete
        <span class="pill">ID <?= h((string)$id) ?></span>
        <span class="pill"><?= h($label) ?></span>
      </p>
      <div class="actions" style="margin-top:14px;">
        <a class="btn" href="<?= h(pf__u($return)) ?>">Back</a>
      </div>
    </div>
  </section>

  <?php if ($notice !== ''): ?><div class="notice success" style="margin-top:14px;"><strong><?= h($notice) ?></strong></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="notice error" style="margin-top:14px;"><strong><?= h($error) ?></strong></div><?php endif; ?>

  <section class="card" style="margin-top:14px;">
    <div class="form-card">
      <form method="post" action="">
        <?= pf__csrf_field() ?>
        <input type="hidden" name="id" value="<?= h((string)$id) ?>">
        <input type="hidden" name="return" value="<?= h($return) ?>">

        <p class="muted" style="margin:0 0 14px;">
          This permanently removes the page record. Related attachment rows in <code>page_files</code> will also be removed first.
        </p>

        <div class="actions" style="display:flex; gap:10px; flex-wrap:wrap;">
          <button class="btn btn-danger" type="submit">Yes, delete page</button>
          <a class="btn" href="<?= h(pf__u($return)) ?>">Cancel</a>
        </div>
      </form>
    </div>
  </section>
</div>
<?php require_once APP_ROOT . '/private/shared/staff_footer.php'; ?>
