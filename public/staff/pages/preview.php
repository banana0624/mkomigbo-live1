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
if (!function_exists('pf__page_title_of')) {
  function pf__page_title_of(array $row): string {
    foreach (['title', 'nav_label', 'menu_name', 'name', 'slug'] as $k) {
      if (isset($row[$k]) && trim((string)$row[$k]) !== '') return trim((string)$row[$k]);
    }
    return 'Page #' . (string)($row['id'] ?? '0');
  }
}
if (!function_exists('pf__page_preview_html')) {
  function pf__page_preview_html(array $row): string {
    if (!empty($row['content_html']) && is_string($row['content_html'])) {
      return (string)$row['content_html'];
    }

    foreach (['body','content','summary','excerpt'] as $k) {
      if (isset($row[$k]) && trim((string)$row[$k]) !== '') {
        return '<div>' . nl2br(h(trim((string)$row[$k]))) . '</div>';
      }
    }

    return '<p><em>No preview content available.</em></p>';
  }
}

$pdo = (function_exists('db') && db() instanceof PDO) ? db() : null;
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

$id = (int)($_GET['id'] ?? 0);
$return = staff_safe_return_url((string)($_GET['return'] ?? ''), '/staff/pages/show.php?id=' . rawurlencode((string)$id));

if ($id <= 0) {
  http_response_code(400);
  echo "Invalid page id.";
  exit;
}

$cols = ['id'];
foreach ([
  'title','nav_label','menu_name','name','slug',
  'content_html','body','content','summary','excerpt',
  'workflow_state','published_at','updated_at'
] as $c) {
  if (pf__column_exists($pdo, 'pages', $c)) $cols[] = $c;
}

$st = $pdo->prepare("SELECT " . implode(', ', array_unique($cols)) . " FROM pages WHERE id = :id LIMIT 1");
$st->execute([':id' => $id]);
$page = $st->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$page) {
  http_response_code(404);
  echo "Page not found.";
  exit;
}

$title = pf__page_title_of($page);
$workflowState = trim((string)($page['workflow_state'] ?? 'draft'));
$publishedAt = trim((string)($page['published_at'] ?? ''));
$updatedAt = trim((string)($page['updated_at'] ?? ''));
$previewHtml = pf__page_preview_html($page);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Preview • <?= h($title) ?></title>
  <style>
    body{margin:0;background:#f6f7f9;color:#111827;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
    .wrap{max-width:1100px;margin:0 auto;padding:24px 16px}
    .bar,.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.05)}
    .bar{padding:14px 16px;margin-bottom:16px}
    .card{padding:24px}
    .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between}
    .pill{display:inline-block;padding:4px 8px;border-radius:999px;border:1px solid #d1d5db;font-size:12px;background:#f9fafb}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 12px;border-radius:10px;border:1px solid #d1d5db;background:#fff;color:#111827;text-decoration:none}
    .muted{color:#6b7280}
    .preview{line-height:1.7}
    .preview img{max-width:100%;height:auto}
    .preview table{border-collapse:collapse;max-width:100%}
    .preview table td,.preview table th{border:1px solid #ddd;padding:8px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="bar">
      <div class="row">
        <div>
          <div style="font-size:20px;font-weight:700"><?= h($title) ?></div>
          <div class="muted" style="margin-top:6px">
            Staff preview
          </div>
        </div>
        <div class="row">
          <span class="pill">State: <?= h($workflowState !== '' ? $workflowState : 'draft') ?></span>
          <?php if ($publishedAt !== ''): ?><span class="pill">Published: <?= h($publishedAt) ?></span><?php endif; ?>
          <?php if ($updatedAt !== ''): ?><span class="pill">Updated: <?= h($updatedAt) ?></span><?php endif; ?>
          <a class="btn" href="<?= h($return) ?>">Back</a>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="preview">
        <?= $previewHtml ?>
      </div>
    </div>
  </div>
</body>
</html>