<?php
declare(strict_types=1);

/**
 * /private/tools/lint/cron_quick_scan.php
 * Cron wrapper: nightly lint quick scan (non-destructive).
 *
 * Robust:
 * - Defines APP_ROOT
 * - Ensures logs/tools exists
 * - Writability probe
 * - Robust lock with stale-lock recovery
 * - Runs quick_scan.php in "cron quiet" mode so it doesn't spam STDOUT
 * - Ensures lock release on shutdown
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!defined('APP_ROOT')) {
  define('APP_ROOT', '/home/mkomigbo/public_html/app/mkomigbo');
}

$appRoot = rtrim((string)APP_ROOT, "/\\");
if ($appRoot === '' || !is_dir($appRoot)) {
  fwrite(STDOUT, "APP_ROOT invalid: {$appRoot}\n");
  exit(1);
}

$logsDir = $appRoot . '/logs/tools';
if (!is_dir($logsDir)) { @mkdir($logsDir, 0755, true); }

/* Writability test */
$probe = $logsDir . '/.write_test';
if (@file_put_contents($probe, "ok\n") === false) {
  fwrite(STDOUT, "logs/tools not writable: {$logsDir}\n");
  exit(1);
}
@unlink($probe);

/* Lock */
$lockFile = $logsDir . '/quick_scan.lock';
$fp = @fopen($lockFile, 'c+');
if (!$fp) {
  fwrite(STDOUT, "Cannot open lock: {$lockFile}\n");
  exit(1);
}

/* Ensure lock release even on fatal shutdown */
$lockHeld = false;
register_shutdown_function(static function() use (&$fp, &$lockHeld): void {
  if (is_resource($fp)) {
    if ($lockHeld) { @flock($fp, LOCK_UN); }
    @fclose($fp);
  }
});

/* Non-blocking lock */
if (!flock($fp, LOCK_EX | LOCK_NB)) {
  clearstatcache(true, $lockFile);
  $age = time() - (int)@filemtime($lockFile);

  // stale recovery if lock file is older than 1 hour
  if ($age > 3600) {
    @ftruncate($fp, 0);
    @rewind($fp);
    @fwrite($fp, "stale_lock_recovered at " . gmdate('c') . "\n");
    if (!flock($fp, LOCK_EX | LOCK_NB)) {
      fwrite(STDOUT, "Lock busy (stale recovery failed).\n");
      exit(0);
    }
  } else {
    fwrite(STDOUT, "Lock busy.\n");
    exit(0);
  }
}

$lockHeld = true;

/* Write lock metadata */
@ftruncate($fp, 0);
@rewind($fp);
@fwrite($fp, "pid=" . getmypid() . " started=" . gmdate('c') . "\n");

/* Quiet cron mode */
$_GET['render'] = 'pre';
$_SERVER['MK_CRON'] = '1';

$tool = $appRoot . '/private/tools/lint/quick_scan.php';
if (!is_file($tool)) {
  fwrite(STDOUT, "Missing tool: {$tool}\n");
  exit(1);
}

/* Run tool quietly */
ob_start();
require $tool;
ob_end_clean();

/* Keep last 30 JSON reports */
$files = @glob($logsDir . '/quick_scan_*.json');
if (is_array($files) && count($files) > 30) {
  usort($files, static fn($a,$b) => (@filemtime((string)$b) ?: 0) <=> (@filemtime((string)$a) ?: 0));
  foreach (array_slice($files, 30) as $old) { @unlink((string)$old); }
}

// Keep trend CSV in sync immediately after every scan
$export = __DIR__ . '/cron_export_history_csv.php';
if (is_file($export)) {
  require $export;
}

/* Release lock (shutdown handler also backs us up) */
@flock($fp, LOCK_UN);
$lockHeld = false;
@fclose($fp);

exit(0);
