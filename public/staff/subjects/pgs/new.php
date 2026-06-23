<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';
auth_require_role('staff');

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('url_for')) {
  function url_for(string $path): string { return '/' . ltrim($path, '/'); }
}
if (!function_exists('redirect_to')) {
  function redirect_to(string $location, int $code = 302): void {
    $location = str_replace(["\r","\n"], '', $location);
    header('Location: ' . $location, true, $code);
    exit;
  }
}
if (!function_exists('pf__u')) {
  function pf__u(string $path): string { return function_exists('url_for') ? (string)url_for($path) : $path; }
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
if (!function_exists('pf__slugify')) {
  function pf__slugify(string $s): string {
    $s = trim($s);
    if ($s === '') return 'page';
    $s = function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    $s = preg_replace('~[^\pL\pN]+~u', '-', $s) ?? '';
    $s = trim($s, '-');
    $s = preg_replace('~-{2,}~', '-', $s) ?? '';
    return $s !== '' ? $s : 'page';
  }
}

$pdo = pf__pdo();
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

$return = pf__safe_return_url((string)($_GET['return'] ?? ($_POST['return'] ?? '')), '/staff/subjects/pgs/index.php');
$notice = pf__flash_get('notice');
$error  = pf__flash_get('error');

$hasSubjectId  = pf__column_exists($pdo, 'pages', 'subject_id');
$hasSlug       = pf__column_exists($pdo, 'pages', 'slug');
$hasTopicGroup = pf__column_exists($pdo, 'pages', 'topic_group');

$titleCol = pf__column_exists($pdo, 'pages', 'title') ? 'title'
         : (pf__column_exists($pdo, 'pages', 'menu_name') ? 'menu_name'
         : (pf__column_exists($pdo, 'pages', 'name') ? 'name' : null));

$bodyCol = pf__column_exists($pdo, 'pages', 'body_html') ? 'body_html'
        : (pf__column_exists($pdo, 'pages', 'body') ? 'body'
        : (pf__column_exists($pdo, 'pages', 'content') ? 'content' : null));

$orderCol = pf__column_exists($pdo, 'pages', 'nav_order') ? 'nav_order'
         : (pf__column_exists($pdo, 'pages', 'position') ? 'position' : null);

$hasIsPublic = pf__column_exists($pdo, 'pages', 'is_public');
$hasVisible  = pf__column_exists($pdo, 'pages', 'visible');
$hasStatus   = pf__column_exists($pdo, 'pages', 'status');
$hasWorkflow = pf__column_exists($pdo, 'pages', 'workflow_state');

$subjects = [];
if ($hasSubjectId) {
  try {
    if (pf__column_exists($pdo, 'subjects', 'menu_name')) {
      $subjects = $pdo->query("SELECT id, menu_name AS title FROM subjects ORDER BY menu_name ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } elseif (pf__column_exists($pdo, 'subjects', 'name')) {
      $subjects = $pdo->query("SELECT id, name AS title FROM subjects ORDER BY name ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
      $tmp = $pdo->query("SELECT id FROM subjects ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
      foreach ($tmp as $r) {
        $sid = (int)($r['id'] ?? 0);
        if ($sid > 0) $subjects[] = ['id' => $sid, 'title' => 'Subject #' . $sid];
      }
    }
  } catch (Throwable $e) {
    $subjects = [];
  }
}

$form = [
  'subject_id' => '',
  'topic_group' => '',
  'title' => '',
  'slug' => '',
  'body' => '',
  'nav_order' => '',
  'is_public' => '0',
];

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
  if (!pf__csrf_verify((string)($_POST['csrf_token'] ?? ''))) {
    pf__flash_set('error', 'Security check failed (CSRF). Please retry.');
    redirect_to(pf__u('/staff/subjects/pgs/new.php?return=' . rawurlencode($return)));
  }

  $form['subject_id']  = trim((string)($_POST['subject_id'] ?? ''));
  $form['topic_group'] = trim((string)($_POST['topic_group'] ?? ''));
  $form['title']       = trim((string)($_POST['title'] ?? ''));
  $form['slug']        = trim((string)($_POST['slug'] ?? ''));
  $form['body']        = (string)($_POST['body'] ?? '');
  $form['nav_order']   = trim((string)($_POST['nav_order'] ?? ''));
  $form['is_public']   = ((string)($_POST['is_public'] ?? '0') === '1') ? '1' : '0';

  if ($hasSubjectId && $form['subject_id'] === '') {
    $error = 'Please choose a subject.';
  } elseif ($titleCol !== null && $form['title'] === '') {
    $error = 'Please enter a title.';
  }

  if ($error === '' && $hasSlug) {
    if ($form['slug'] === '') $form['slug'] = pf__slugify($form['title'] !== '' ? $form['title'] : 'page');
    $form['slug'] = strtolower($form['slug']);
    if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $form['slug'])) {
      $error = 'Slug must be lowercase and contain only letters, numbers, underscore or dash.';
    }
  }

  if ($error === '' && $hasTopicGroup && $form['topic_group'] !== '') {
    $form['topic_group'] = strtolower($form['topic_group']);
    if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,64}$/', $form['topic_group'])) {
      $error = 'Topic group must be lowercase and contain only letters, numbers, underscore or dash.';
    }
  }

  if ($error === '' && $hasSubjectId && $hasSlug) {
    if ($hasTopicGroup) {
      $chk = $pdo->prepare("SELECT id FROM pages WHERE subject_id = :sid AND slug = :slug AND (topic_group <=> :tg) LIMIT 1");
      $chk->bindValue(':sid', (int)$form['subject_id'], PDO::PARAM_INT);
      $chk->bindValue(':slug', (string)$form['slug'], PDO::PARAM_STR);
      if ($form['topic_group'] === '') $chk->bindValue(':tg', null, PDO::PARAM_NULL);
      else $chk->bindValue(':tg', (string)$form['topic_group'], PDO::PARAM_STR);
      $chk->execute();
    } else {
      $chk = $pdo->prepare("SELECT id FROM pages WHERE subject_id = :sid AND slug = :slug LIMIT 1");
      $chk->execute([':sid' => (int)$form['subject_id'], ':slug' => (string)$form['slug']]);
    }
    if ((int)$chk->fetchColumn() > 0) {
      $error = $hasTopicGroup
        ? 'A page with this Subject + Topic group + Slug already exists.'
        : 'A page with this Subject + Slug already exists.';
    }
  }

  if ($error === '') {
    try {
      $fields = [];
      $holders = [];
      $bind = [];

      if ($hasSubjectId) { $fields[] = 'subject_id'; $holders[] = ':subject_id'; $bind[':subject_id'] = (int)$form['subject_id']; }
      if ($hasTopicGroup) {
        $fields[] = 'topic_group';
        if ($form['topic_group'] === '') {
          $holders[] = 'NULL';
        } else {
          $holders[] = ':topic_group';
          $bind[':topic_group'] = (string)$form['topic_group'];
        }
      }
      if ($titleCol !== null) { $fields[] = $titleCol; $holders[] = ':title'; $bind[':title'] = (string)$form['title']; }
      if ($hasSlug) { $fields[] = 'slug'; $holders[] = ':slug'; $bind[':slug'] = (string)$form['slug']; }
      if ($bodyCol !== null) { $fields[] = $bodyCol; $holders[] = ':body'; $bind[':body'] = (string)$form['body']; }
      if ($orderCol !== null) {
        $fields[] = $orderCol;
        if ($form['nav_order'] === '') $holders[] = 'NULL';
        else { $holders[] = ':nav_order'; $bind[':nav_order'] = (int)$form['nav_order']; }
      }

      if ($hasIsPublic) {
        $fields[] = 'is_public';
        $holders[] = ':is_public';
        $bind[':is_public'] = ((string)$form['is_public'] === '1') ? 1 : 0;
      } elseif ($hasVisible) {
        $fields[] = 'visible';
        $holders[] = ':visible';
        $bind[':visible'] = ((string)$form['is_public'] === '1') ? 1 : 0;
      } elseif ($hasStatus) {
        $fields[] = 'status';
        $holders[] = ':status';
        $bind[':status'] = ((string)$form['is_public'] === '1') ? 'published' : 'draft';
      } elseif ($hasWorkflow) {
        $fields[] = 'workflow_state';
        $holders[] = ':workflow_state';
        $bind[':workflow_state'] = ((string)$form['is_public'] === '1') ? 'published' : 'draft';
      }

      $sql = "INSERT INTO pages (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $holders) . ")";
      $st = $pdo->prepare($sql);
      foreach ($bind as $k => $v) {
        if (is_int($v)) $st->bindValue($k, $v, PDO::PARAM_INT);
        else $st->bindValue($k, (string)$v, PDO::PARAM_STR);
      }
      $st->execute();

      $newId = (int)$pdo->lastInsertId();
      pf__flash_set('notice', 'Page created.');
      redirect_to(pf__u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$newId) . '&return=' . rawurlencode($return)));
    } catch (Throwable $e) {
      $error = 'Create failed: ' . $e->getMessage();
    }
  }
}

$page_title = 'New Page • Staff';
$active_nav = 'pgs';
require_once APP_ROOT . '/app/mkomigbo/private/shared/staff_header.php';
?>
<div class="container" style="padding:24px 0;">
  <section class="hero">
    <div class="hero-bar"></div>
    <div class="hero-inner">
      <h1>New Page</h1>
      <p class="muted" style="margin:6px 0 0;">Create a new page record for a subject.</p>
      <div class="actions" style="margin-top:14px;">
        <a class="btn" href="<?= h(pf__u($return)) ?>">Back to pages</a>
      </div>
    </div>
  </section>

  <?php if ($notice !== ''): ?><div class="notice success" style="margin-top:14px;"><strong><?= h($notice) ?></strong></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="notice error" style="margin-top:14px;"><strong><?= h($error) ?></strong></div><?php endif; ?>

  <section class="card" style="margin-top:14px;">
    <div class="form-card">
      <form method="post" action="">
        <?= pf__csrf_field() ?>
        <input type="hidden" name="return" value="<?= h($return) ?>">

        <?php if ($hasSubjectId): ?>
          <div class="field">
            <label class="label" for="subject_id">Subject</label>
            <select class="input" id="subject_id" name="subject_id" required>
              <option value="">Choose subject</option>
              <?php foreach ($subjects as $s): ?>
                <?php $sid = (int)($s['id'] ?? 0); ?>
                <option value="<?= $sid ?>" <?= ((string)$sid === $form['subject_id']) ? 'selected' : '' ?>>
                  <?= h((string)($s['title'] ?? ('Subject #' . $sid))) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($hasTopicGroup): ?>
          <div class="field">
            <label class="label" for="topic_group">Topic group</label>
            <input class="input mono" id="topic_group" name="topic_group" value="<?= h($form['topic_group']) ?>" placeholder="optional">
          </div>
        <?php endif; ?>

        <div class="field">
          <label class="label" for="title">Title</label>
          <input class="input" id="title" name="title" value="<?= h($form['title']) ?>" required>
        </div>

        <?php if ($hasSlug): ?>
          <div class="field">
            <label class="label" for="slug">Slug</label>
            <input class="input mono" id="slug" name="slug" value="<?= h($form['slug']) ?>" placeholder="auto-generated if blank">
          </div>
        <?php endif; ?>

        <?php if ($orderCol !== null): ?>
          <div class="field">
            <label class="label" for="nav_order"><?= h($orderCol === 'position' ? 'Position' : 'Nav order') ?></label>
            <input class="input mono" id="nav_order" name="nav_order" value="<?= h($form['nav_order']) ?>" placeholder="blank allowed">
          </div>
        <?php endif; ?>

        <div class="field">
          <label class="label" for="is_public">Visibility</label>
          <select class="input" id="is_public" name="is_public">
            <option value="0" <?= $form['is_public'] === '0' ? 'selected' : '' ?>>Draft / Private</option>
            <option value="1" <?= $form['is_public'] === '1' ? 'selected' : '' ?>>Published / Public</option>
          </select>
        </div>

        <?php if ($bodyCol !== null): ?>
          <div class="field">
            <label class="label" for="body">Body</label>
            <textarea class="input" id="body" name="body" rows="14"><?= h($form['body']) ?></textarea>
          </div>
        <?php endif; ?>

        <div class="actions" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:14px;">
          <button class="btn btn-primary" type="submit">Create page</button>
          <a class="btn" href="<?= h(pf__u($return)) ?>">Cancel</a>
        </div>
      </form>
    </div>
  </section>
</div>
<?php require_once APP_ROOT . '/app/mkomigbo/private/shared/staff_footer.php'; ?>
