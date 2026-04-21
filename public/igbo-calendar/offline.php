<?php
declare(strict_types=1);

// /public/igbo-calendar/offline.php
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

if (!function_exists('h')) {
    function h(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}

$u = static function (string $path): string {
    return function_exists('url_for') ? (string) url_for($path) : $path;
};

$asset = static function (string $path) use ($u): string {
    if (function_exists('mk_asset_ver')) {
        try {
            return (string) mk_asset_ver($path);
        } catch (Throwable $e) {
            // ignore
        }
    }
    return $u($path);
};

$title = 'Offline • Igbo Calendar';
$desc  = 'Igbo Calendar is offline right now. You can reopen cached pages or reconnect and refresh.';

$css_main = $asset('/igbo-calendar/igbo-calendar.css');
$pwa_hook = $asset('/igbo-calendar/pwa-hook.js');

$app_url     = $u('/igbo-calendar/');
$install_url = $u('/igbo-calendar/install/');
$retry_url   = $u('/igbo-calendar/?pwa=1');

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="en" class="igcal-offline-page">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="referrer" content="strict-origin-when-cross-origin">
  <meta name="theme-color" content="#0b0b0b">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">

  <title><?php echo h($title); ?></title>
  <meta name="description" content="<?php echo h($desc); ?>">

  <link rel="stylesheet" href="<?php echo h($css_main); ?>">

  <style>
    .igcal-offline-wrap{
      min-height:100vh;
      display:grid;
      place-items:center;
      padding:24px 12px;
    }

    .igcal-offline-card{
      width:min(100%, 760px);
      border:1px solid var(--igcal-border);
      background:linear-gradient(180deg,var(--igcal-surface),var(--igcal-surface-2));
      border-radius:var(--igcal-radius);
      box-shadow:var(--igcal-shadow);
      overflow:hidden;
    }

    .igcal-offline-inner{
      padding:24px;
    }

    .igcal-offline-kicker{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid var(--igcal-border);
      background:var(--igcal-surface-2);
      color:var(--igcal-muted);
      font-size:12px;
      font-weight:700;
      letter-spacing:.04em;
      text-transform:uppercase;
    }

    .igcal-offline-title{
      margin:14px 0 8px;
      font-size:32px;
      line-height:1.1;
      color:var(--igcal-accent-2);
    }

    .igcal-offline-copy{
      margin:0;
      max-width:62ch;
      color:var(--igcal-text);
      line-height:1.6;
    }

    .igcal-offline-facts{
      display:grid;
      gap:10px;
      margin:18px 0 0;
    }

    .igcal-offline-fact{
      padding:10px 12px;
      border:1px solid var(--igcal-border);
      border-radius:12px;
      background:rgba(255,250,242,.58);
      color:var(--igcal-text);
    }

    .igcal-offline-actions{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      margin-top:18px;
    }

    .igcal-offline-btn{
      appearance:none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:44px;
      padding:11px 14px;
      border-radius:12px;
      border:1px solid var(--igcal-border);
      background:linear-gradient(180deg, #fff5e6 0%, #f2e0bf 100%);
      color:var(--igcal-text);
      text-decoration:none;
      font-weight:700;
      box-shadow:var(--igcal-shadow-sm);
    }

    .igcal-offline-btn--primary{
      border-color:var(--igcal-accent-2);
      background:linear-gradient(180deg, #a76d3d 0%, #7a4a25 100%);
      color:#fff;
    }

    .igcal-offline-status{
      margin-top:16px;
      min-height:1.4em;
      color:var(--igcal-muted);
      font-size:14px;
    }

    @media (max-width: 640px){
      .igcal-offline-inner{
        padding:18px;
      }

      .igcal-offline-title{
        font-size:26px;
      }

      .igcal-offline-actions{
        flex-direction:column;
      }

      .igcal-offline-btn{
        width:100%;
      }
    }
  </style>
</head>
<body class="igcal-body igcal-offline">
  <div class="igcal-offline-wrap">
    <main class="igcal-offline-card" aria-labelledby="offline-title">
      <div class="igcal-offline-inner">
        <div class="igcal-offline-kicker">Offline</div>

        <h1 id="offline-title" class="igcal-offline-title">You are offline right now</h1>

        <p class="igcal-offline-copy">
          The Igbo Calendar app could not load this page from the network.
          If core files were cached earlier, reopening the main app may still work.
          Once your connection returns, refresh the app to resync the latest content.
        </p>

        <div class="igcal-offline-facts" aria-label="Offline guidance">
          <div class="igcal-offline-fact"><strong>Main app:</strong> <?php echo h('/igbo-calendar/'); ?></div>
          <div class="igcal-offline-fact"><strong>Install helper:</strong> <?php echo h('/igbo-calendar/install/'); ?></div>
          <div class="igcal-offline-fact"><strong>Best recovery:</strong> reconnect, then reopen the main app and refresh once.</div>
        </div>

        <div class="igcal-offline-actions">
          <a class="igcal-offline-btn igcal-offline-btn--primary" href="<?php echo h($retry_url); ?>">Retry App</a>
          <a class="igcal-offline-btn" href="<?php echo h($app_url); ?>">Open Main Calendar</a>
          <a class="igcal-offline-btn" href="<?php echo h($install_url); ?>">Install Help</a>
          <button class="igcal-offline-btn" type="button" id="igcalReloadBtn">Reload This Page</button>
        </div>

        <div id="igcalOfflineStatus" class="igcal-offline-status" aria-live="polite">
          Waiting for connection status…
        </div>
      </div>
    </main>
  </div>

  <script src="<?php echo h($pwa_hook); ?>" defer></script>
  <script>
    (function () {
      function updateStatus() {
        var box = document.getElementById('igcalOfflineStatus');
        if (!box) return;
        box.textContent = navigator.onLine
          ? 'Connection detected. You can retry the app now.'
          : 'Still offline. Cached pages may still open if they were saved earlier.';
      }

      document.addEventListener('DOMContentLoaded', function () {
        var reloadBtn = document.getElementById('igcalReloadBtn');
        if (reloadBtn) {
          reloadBtn.addEventListener('click', function () {
            window.location.reload();
          });
        }

        updateStatus();
        window.addEventListener('online', updateStatus);
        window.addEventListener('offline', updateStatus);
      });
    })();
  </script>
</body>
</html>