<?php
declare(strict_types=1);

/**
 * /public/igbo-calendar/install/index.php
 * Public: Install helper + diagnostics page for Igbo Calendar PWA.
 *
 * Contract:
 * - Always render the 5 controls:
 *   Open the App, Download Export, Install App, Run Install Check, Copy Debug
 * - Do NOT pretend we can force-install on demand. Browsers gate that.
 * - install.js owns diagnostics + (optionally) wiring install prompt if available.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../../_init.php';

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

$u = static function(string $path): string {
  return function_exists('url_for') ? (string)url_for($path) : $path;
};

$asset = static function(string $path) use ($u): string {
  if (function_exists('mk_asset_ver')) {
    try { return (string) mk_asset_ver($path); } catch (Throwable $e) {}
  }
  return $u($path);
};

/* ---------------------------------------------------------
   Page vars (shared header contract)
--------------------------------------------------------- */
$brand_name = defined('MK_BRAND_NAME') ? (string) MK_BRAND_NAME : 'Mkomigbo';
$page_title = 'Install Igbo Calendar • ' . $brand_name;
$page_desc  = 'Install the Igbo Calendar as an app (PWA) with offline support.';
$nav_active = 'igbo-calendar';
$active_nav = 'igbo-calendar';

$manifest_url = $u('/igbo-calendar/manifest.json');
$sw_url       = $u('/igbo-calendar/service-worker.js');

/**
 * IMPORTANT:
 * Put ui.css FIRST, then install.css.
 * - If your shared header already includes ui.css, this is slightly redundant but safe.
 * - If it does NOT include ui.css, this guarantees correct styling.
 */
$extra_css = [
  $asset('/lib/css/ui.css'),
  $asset('/igbo-calendar/install/install.css'),
];

$extra_head = ''
  . '<link rel="manifest" href="' . h($manifest_url) . '">' . "\n"
  . '<meta name="theme-color" content="#ffffff">' . "\n"
  . '<meta name="mobile-web-app-capable" content="yes">' . "\n"
  . '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n"
  . '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";

$GLOBALS['page_title'] = $page_title;
$GLOBALS['page_desc']  = $page_desc;
$GLOBALS['nav_active'] = $nav_active;
$GLOBALS['active_nav'] = $active_nav;
$GLOBALS['extra_css']  = $extra_css;
$GLOBALS['extra_head'] = $extra_head;

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'nav_active' => $nav_active,
      'active_nav' => $active_nav,
      'extra_css'  => $extra_css,
      'extra_head' => $extra_head,
    ]);
  } catch (Throwable $e) {}
}

/* ---------------------------------------------------------
   Header
--------------------------------------------------------- */
$header_ok = false;
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_header.php'); $header_ok = true; } catch (Throwable $e) {}
}

if (!$header_ok) {
  header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><html lang='en'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo $extra_head;
  echo "<title>" . h($page_title) . "</title>\n";

  // Fallback header must also include CSS explicitly:
  foreach ($extra_css as $href) {
    echo '<link rel="stylesheet" href="' . h((string)$href) . '">' . "\n";
  }

  echo "</head><body>";
}

/* ---------------------------------------------------------
   URLs
--------------------------------------------------------- */
$app_url      = $u('/igbo-calendar/?pwa=1');
$download_url = $u('/igbo-calendar/download/');
$install_js   = $asset('/igbo-calendar/install/install.js');
$pwa_hook_js  = $asset('/igbo-calendar/pwa-hook.js'); // optional helper; safe if present

?>
<main class="container" style="padding:24px 0;">

  <h1>Install Igbo Calendar</h1>

  <p class="muted" style="max-width:75ch;">
    This page provides install guidance and diagnostics. The actual install prompt is controlled by your browser
    and is most likely to become available on the main app page.
  </p>

  <div class="mk-card mk-card--soft" style="padding:14px; margin:14px 0;">
    <div>
      <strong>Start URL:</strong> <?= h('/igbo-calendar/?pwa=1') ?>
      <strong>Scope:</strong> <?= h('/igbo-calendar/') ?>
    </div>
    <div class="muted" style="margin-top:6px;">
      For desktop Edge/Chrome: open the app page first, refresh once, then check the browser menu or address bar install icon.
    </div>
  </div>

  <!-- Controls: ALWAYS present -->
  <div class="mk-install-actions" style="display:flex; gap:10px; flex-wrap:wrap; margin:14px 0;">
    <a class="btn" href="<?= h($app_url) ?>">Open the App</a>

    <!-- Download should be a plain <a> so it works even if JS fails -->
    <a class="btn btn--ghost" href="<?= h($download_url) ?>" download>Download export</a>

    <!-- IDs MUST match install.js -->
    <button class="btn" id="btnInstall" type="button" disabled>Install App</button>
    <button class="btn btn--ghost" id="btnCheck" type="button">Run Install Check</button>
    <button class="btn btn--ghost" id="btnCopyDebug" type="button">Copy Debug</button>

    <!-- Optional helper (does not break anything if SW ignores messages) -->
    <button class="btn btn--ghost" id="btnSwRefresh" type="button">Refresh Service Worker</button>
  </div>

  <div id="installStatus" class="mk-alert" style="margin-top:10px;">
    Checking install support…
  </div>

  <h2 style="margin-top:18px;">Install guidance</h2>

  <div class="mk-alert" style="margin-top:10px;">
    <strong>Desktop / Android (Chrome, Edge):</strong>
    <ul style="margin:8px 0 0 18px;">
      <li>Open <a href="<?= h($app_url) ?>"><?= h('/igbo-calendar/?pwa=1') ?></a>.</li>
      <li>Refresh once (Ctrl+Shift+R) and click any UI element.</li>
      <li>Look for an install icon in the address bar, or use menu (⋯) → <strong>Install app</strong>.</li>
      <li>If you still see no install option, your browser has decided you are not yet eligible (engagement/heuristics).</li>
    </ul>
  </div>

  <div class="mk-alert" style="margin-top:10px;">
    <strong>iPhone / iPad (Safari):</strong>
    <ul style="margin:8px 0 0 18px;">
      <li>Open the app page in Safari.</li>
      <li>Tap <strong>Share</strong> → <strong>Add to Home Screen</strong>.</li>
    </ul>
  </div>

  <h2 style="margin-top:18px;">Diagnostics</h2>
  <div class="muted" style="margin-bottom:10px;">
    App: <?= h('/igbo-calendar/?pwa=1') ?><br>
    Manifest: <?= h($manifest_url) ?><br>
    Service worker: <?= h($sw_url) ?><br>
    Install script: <?= h('/igbo-calendar/install/install.js') ?><br>
    Hook: <?= h('/igbo-calendar/pwa-hook.js') ?>
  </div>

  <pre id="diagBox" class="mk-install-debug"></pre>

</main>

<script>
(function(){
  // Small helper: request SW to update/activate immediately (non-fatal if unsupported)
  var btn = document.getElementById('btnSwRefresh');
  if (!btn) return;

  btn.addEventListener('click', function(){
    var status = document.getElementById('installStatus');
    try { if (status) status.textContent = 'Requesting service worker refresh…'; } catch(e){}

    if (!('serviceWorker' in navigator)) {
      try { if (status) status.textContent = 'Service workers not supported in this browser.'; } catch(e){}
      return;
    }

    navigator.serviceWorker.getRegistration('<?= h($u("/igbo-calendar/")) ?>')
      .then(function(reg){
        if (!reg) return null;
        try { reg.update(); } catch(e){}
        var target = reg.waiting || reg.installing || reg.active;
        if (target) {
          try { target.postMessage({ type: 'SKIP_WAITING' }); } catch(e){}
        }
        return reg;
      })
      .then(function(){
        try { if (status) status.textContent = 'Service worker refresh requested. Now open the app page and refresh once.'; } catch(e){}
      })
      .catch(function(){
        try { if (status) status.textContent = 'Could not refresh service worker. Open the app page and refresh once.'; } catch(e){}
      });
  });
})();
</script>

<script src="<?= h($install_js) ?>" defer></script>
<script src="<?= h($pwa_hook_js) ?>" defer></script>

<?php
if (function_exists('mk_require_shared')) {
  mk_require_shared('public_footer.php');
} else {
  echo "</body></html>";
}
