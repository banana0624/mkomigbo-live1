<?php
declare(strict_types=1);

/**
 * /public/igbo-calendar/install/index.php
 * Public install helper + diagnostics page for Igbo Calendar PWA.
 *
 * Contract:
 * - Keep existing control IDs so install/install.js continues to work
 * - Present clearer install state and diagnostics
 * - Preserve shared header/footer compatibility
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../../_init.php';

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
            // fall through
        }
    }
    return $u($path);
};

/* ---------------------------------------------------------
   Page vars (shared header contract)
--------------------------------------------------------- */
$brand_name = defined('MK_BRAND_NAME') ? (string) MK_BRAND_NAME : 'Mkomigbo';
$page_title = 'Install Igbo Calendar • ' . $brand_name;
$page_desc  = 'Install the Igbo Calendar as an app, verify PWA readiness, and inspect diagnostics.';
$nav_active = 'igbo-calendar';
$active_nav = 'igbo-calendar';

$manifest_url = $u('/igbo-calendar/manifest.json');
$sw_url       = $u('/igbo-calendar/service-worker.js');
$offline_url  = $u('/igbo-calendar/offline.php');

$extra_css = [
    $asset('/lib/css/ui.css'),
    $asset('/igbo-calendar/install/install.css'),
];

$extra_head = ''
    . '<link rel="manifest" href="' . h($manifest_url) . '">' . "\n"
    . '<meta name="theme-color" content="#0b0b0b">' . "\n"
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
    } catch (Throwable $e) {
        // ignore
    }
}

/* ---------------------------------------------------------
   Header
--------------------------------------------------------- */
$header_ok = false;

if (function_exists('mk_require_shared')) {
    try {
        mk_require_shared('public_header.php');
        $header_ok = true;
    } catch (Throwable $e) {
        $header_ok = false;
    }
}

if (!$header_ok) {
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!doctype html>\n";
    echo "<html lang=\"en\">\n";
    echo "<head>\n";
    echo "  <meta charset=\"utf-8\">\n";
    echo "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
    echo $extra_head;
    echo '  <title>' . h($page_title) . "</title>\n";

    foreach ($extra_css as $href) {
        echo '  <link rel="stylesheet" href="' . h((string) $href) . "\">\n";
    }

    echo "</head>\n";
    echo "<body>\n";
}

/* ---------------------------------------------------------
   URLs
--------------------------------------------------------- */
$app_url      = $u('/igbo-calendar/?pwa=1');
$plain_app    = $u('/igbo-calendar/');
$download_url = $u('/igbo-calendar/download/');
$install_js   = $asset('/igbo-calendar/install/install.js');
$pwa_hook_js  = $asset('/igbo-calendar/pwa-hook.js');

?>
<main class="container" style="padding:24px 0;">
  <style>
    .ig-install-shell{
      display:grid;
      gap:16px;
    }

    .ig-install-hero,
    .ig-install-card{
      border:1px solid rgba(184,156,115,.22);
      border-radius:16px;
      background:linear-gradient(180deg, rgba(255,250,242,.96), rgba(244,235,219,.96));
      box-shadow:0 8px 22px rgba(43,34,24,.06);
      overflow:hidden;
    }

    .ig-install-hero__body,
    .ig-install-card__body{
      padding:18px;
    }

    .ig-install-kicker{
      display:inline-flex;
      align-items:center;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid rgba(184,156,115,.28);
      background:rgba(255,248,236,.88);
      color:#6f4724;
      font-size:12px;
      font-weight:700;
      letter-spacing:.04em;
      text-transform:uppercase;
    }

    .ig-install-title{
      margin:12px 0 8px;
      color:#6f4724;
      font-size:32px;
      line-height:1.1;
    }

    .ig-install-copy{
      margin:0;
      max-width:76ch;
      line-height:1.6;
      color:#2b2218;
    }

    .ig-install-actions{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      margin-top:16px;
    }

    .ig-install-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0, 1fr));
      gap:16px;
    }

    .ig-install-facts{
      display:grid;
      gap:10px;
    }

    .ig-install-fact{
      padding:10px 12px;
      border:1px solid rgba(184,156,115,.22);
      border-radius:12px;
      background:rgba(255,248,236,.70);
      line-height:1.45;
      color:#2b2218;
    }

    .ig-install-fact strong{
      color:#6f4724;
    }

    .ig-install-status{
      margin-top:0;
      padding:14px 16px;
      border-radius:12px;
      border:1px solid rgba(184,156,115,.22);
      background:rgba(255,248,236,.82);
      line-height:1.45;
    }

    .ig-install-status[data-tone="ok"]{
      border-color:rgba(47,107,61,.28);
      background:rgba(47,107,61,.08);
    }

    .ig-install-status[data-tone="warn"]{
      border-color:rgba(199,154,59,.34);
      background:rgba(199,154,59,.10);
    }

    .ig-install-status[data-tone="bad"]{
      border-color:rgba(160,51,51,.28);
      background:rgba(160,51,51,.08);
    }

    .ig-install-meta{
      margin-top:8px;
      min-height:1.2em;
      color:#6b5a45;
      font-size:14px;
    }

    .ig-install-state-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0, 1fr));
      gap:10px;
      margin-top:14px;
    }

    .ig-install-state{
      padding:12px;
      border:1px solid rgba(184,156,115,.22);
      border-radius:12px;
      background:rgba(255,248,236,.66);
    }

    .ig-install-state__label{
      display:block;
      color:#6b5a45;
      font-size:12px;
      margin-bottom:4px;
    }

    .ig-install-state__value{
      display:block;
      color:#2b2218;
      font-size:15px;
      font-weight:800;
      line-height:1.3;
    }

    .ig-install-guides{
      display:grid;
      gap:10px;
    }

    .ig-install-guide{
      padding:12px 14px;
      border:1px solid rgba(184,156,115,.22);
      border-radius:12px;
      background:rgba(255,248,236,.62);
    }

    .ig-install-guide strong{
      color:#6f4724;
    }

    .ig-install-guide ul{
      margin:8px 0 0 18px;
    }

    .ig-install-debug{
      min-height:260px;
      margin:0;
      padding:14px;
      border-radius:12px;
      border:1px solid rgba(184,156,115,.22);
      background:#1b1712;
      color:#f6efe3;
      font:12px/1.5 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
      white-space:pre-wrap;
      word-break:break-word;
      overflow:auto;
    }

    .ig-install-note{
      color:#6b5a45;
      line-height:1.55;
    }

    @media (prefers-color-scheme: dark){
      .ig-install-hero,
      .ig-install-card{
        border-color:rgba(224,194,145,.16);
        background:linear-gradient(180deg, rgba(255,248,240,.06), rgba(255,248,240,.09));
        box-shadow:0 10px 30px rgba(0,0,0,.22);
      }

      .ig-install-kicker,
      .ig-install-fact,
      .ig-install-state,
      .ig-install-guide,
      .ig-install-status{
        background:rgba(255,248,240,.05);
        color:rgba(255,248,240,.92);
      }

      .ig-install-title{
        color:rgba(235,197,135,.96);
      }

      .ig-install-copy,
      .ig-install-note{
        color:rgba(255,248,240,.90);
      }

      .ig-install-fact strong,
      .ig-install-guide strong{
        color:rgba(235,197,135,.96);
      }

      .ig-install-meta,
      .ig-install-state__label{
        color:rgba(255,248,240,.68);
      }

      .ig-install-state__value{
        color:rgba(255,248,240,.94);
      }
    }

    @media (max-width: 860px){
      .ig-install-grid,
      .ig-install-state-grid{
        grid-template-columns:1fr;
      }
    }
  </style>

  <section class="ig-install-shell">
    <section class="ig-install-hero">
      <div class="ig-install-hero__body">
        <span class="ig-install-kicker">PWA Install & Diagnostics</span>
        <h1 class="ig-install-title">Install Igbo Calendar</h1>
        <p class="ig-install-copy">
          Use this page to install the calendar as an app, verify install readiness, refresh the service worker,
          and copy a support-friendly diagnostics report. The actual install target remains the main app at
          <code>/igbo-calendar/</code>.
        </p>

        <div class="ig-install-actions">
          <a class="btn" href="<?= h($app_url) ?>">Open the App</a>
          <a class="btn btn--ghost" href="<?= h($download_url) ?>" download>Download Export</a>
          <button class="btn" id="btnInstall" type="button" disabled>Install App</button>
          <button class="btn btn--ghost" id="btnCheck" type="button">Run Install Check</button>
          <button class="btn btn--ghost" id="btnCopyDebug" type="button">Copy Debug</button>
          <button class="btn btn--ghost" id="btnSwRefresh" type="button">Refresh Service Worker</button>
        </div>
      </div>
    </section>

    <div class="ig-install-grid">
      <section class="ig-install-card">
        <div class="ig-install-card__body">
          <h2 style="margin-top:0;">Current status</h2>

          <div id="installStatus" class="ig-install-status" data-tone="warn">
            Install status not checked yet.
          </div>

          <div id="installMeta" class="ig-install-meta">
            Last diagnostics update: not yet run.
          </div>

          <div class="ig-install-state-grid">
            <div class="ig-install-state">
              <span class="ig-install-state__label">Standalone Mode</span>
              <span class="ig-install-state__value" id="igDiagStandalone">Checking…</span>
            </div>
            <div class="ig-install-state">
              <span class="ig-install-state__label">Online Status</span>
              <span class="ig-install-state__value" id="igDiagOnline">Checking…</span>
            </div>
            <div class="ig-install-state">
              <span class="ig-install-state__label">Install Prompt</span>
              <span class="ig-install-state__value" id="igDiagPrompt">Checking…</span>
            </div>
            <div class="ig-install-state">
              <span class="ig-install-state__label">Service Worker</span>
              <span class="ig-install-state__value" id="igDiagSW">Checking…</span>
            </div>
          </div>

          <div class="ig-install-facts" style="margin-top:14px;">
            <div class="ig-install-fact"><strong>App URL:</strong> <?= h('/igbo-calendar/') ?></div>
            <div class="ig-install-fact"><strong>Start URL:</strong> <?= h('/igbo-calendar/?pwa=1') ?></div>
            <div class="ig-install-fact"><strong>Scope:</strong> <?= h('/igbo-calendar/') ?></div>
            <div class="ig-install-fact"><strong>Offline Fallback:</strong> <?= h($offline_url) ?></div>
          </div>
        </div>
      </section>

      <section class="ig-install-card">
        <div class="ig-install-card__body">
          <h2 style="margin-top:0;">Install guidance</h2>

          <div class="ig-install-guides">
            <div class="ig-install-guide">
              <strong>Android / Desktop (Chrome, Edge, Opera)</strong>
              <ul>
                <li>Open <a href="<?= h($app_url) ?>"><?= h('/igbo-calendar/?pwa=1') ?></a> or <a href="<?= h($plain_app) ?>"><?= h('/igbo-calendar/') ?></a>.</li>
                <li>Wait for the page to finish loading fully.</li>
                <li>Refresh once if needed after a deployment.</li>
                <li>Use the address-bar install icon or the browser menu entry for <strong>Install app</strong> / <strong>Add to Home screen</strong>.</li>
              </ul>
            </div>

            <div class="ig-install-guide">
              <strong>iPhone / iPad (Safari)</strong>
              <ul>
                <li>Open the main app page in Safari.</li>
                <li>Tap <strong>Share</strong>.</li>
                <li>Choose <strong>Add to Home Screen</strong>.</li>
              </ul>
            </div>

            <div class="ig-install-guide">
              <strong>Important</strong><br>
              Install from the main calendar page at <code>/igbo-calendar/</code>. This helper page is for diagnostics and service-worker maintenance.
            </div>
          </div>
        </div>
      </section>
    </div>

    <section class="ig-install-card">
      <div class="ig-install-card__body">
        <h2 style="margin-top:0;">Diagnostics report</h2>
        <p class="ig-install-note" style="margin-top:0;">
          The report below is formatted for readability and support. “Copy Debug” will copy a structured payload with
          page URL, install readiness, service-worker status, manifest/service-worker paths, user agent, and timestamp.
        </p>

        <div class="ig-install-facts" style="margin-bottom:12px;">
          <div class="ig-install-fact"><strong>Main app:</strong> <?= h('/igbo-calendar/') ?></div>
          <div class="ig-install-fact"><strong>Manifest:</strong> <?= h($manifest_url) ?></div>
          <div class="ig-install-fact"><strong>Service worker:</strong> <?= h($sw_url) ?></div>
          <div class="ig-install-fact"><strong>Offline page:</strong> <?= h($offline_url) ?></div>
          <div class="ig-install-fact"><strong>Install script:</strong> <?= h('/igbo-calendar/install/install.js') ?></div>
          <div class="ig-install-fact"><strong>Hook:</strong> <?= h('/igbo-calendar/pwa-hook.js') ?></div>
        </div>

        <pre id="diagBox" class="ig-install-debug">Diagnostics not yet run.</pre>
      </div>
    </section>
  </section>
</main>

<script src="<?= h($install_js) ?>" defer></script>
<script src="<?= h($pwa_hook_js) ?>" defer></script>

<script>
(function () {
  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function setText(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value;
  }

  function isStandalone() {
    try {
      return !!(
        (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
        window.navigator.standalone === true
      );
    } catch (_) {
      return false;
    }
  }

  async function getSWState() {
    try {
      if (!('serviceWorker' in navigator)) return 'Not supported';
      var reg = await navigator.serviceWorker.getRegistration('/igbo-calendar/');
      if (!reg) return 'Not registered';
      if (reg.waiting) return 'Waiting';
      if (reg.installing) return 'Installing';
      if (reg.active) return 'Active';
      return 'Registered';
    } catch (_) {
      return 'Unknown';
    }
  }

  function getInstallPromptState() {
    try {
      if (typeof window.igcalCanInstall === 'function') {
        return window.igcalCanInstall() ? 'Available' : 'Not available';
      }
    } catch (_) {}
    return 'Unknown';
  }

  function nowStamp() {
    try {
      return new Date().toISOString();
    } catch (_) {
      return '';
    }
  }

  async function buildDiagnosticsPayload() {
    var payload = {
      page_url: window.location.href,
      main_app_url: '/igbo-calendar/',
      manifest_url: '/igbo-calendar/manifest.json',
      service_worker_url: '/igbo-calendar/service-worker.js',
      offline_url: '/igbo-calendar/offline.php',
      standalone_mode: isStandalone(),
      online: navigator.onLine,
      install_prompt_available: getInstallPromptState(),
      service_worker_state: await getSWState(),
      has_sw_controller: !!(navigator.serviceWorker && navigator.serviceWorker.controller),
      user_agent: navigator.userAgent || '',
      timestamp_utc: nowStamp()
    };

    return payload;
  }

  function prettyBool(v) {
    return v ? 'Yes' : 'No';
  }

  function formatPayload(payload) {
    return [
      'Igbo Calendar Install Diagnostics',
      '================================',
      '',
      'Page URL: ' + payload.page_url,
      'Main App URL: ' + payload.main_app_url,
      'Manifest URL: ' + payload.manifest_url,
      'Service Worker URL: ' + payload.service_worker_url,
      'Offline URL: ' + payload.offline_url,
      '',
      'Standalone Mode: ' + prettyBool(!!payload.standalone_mode),
      'Online: ' + prettyBool(!!payload.online),
      'Install Prompt Available: ' + payload.install_prompt_available,
      'Service Worker State: ' + payload.service_worker_state,
      'Has Service Worker Controller: ' + prettyBool(!!payload.has_sw_controller),
      '',
      'User Agent:',
      payload.user_agent,
      '',
      'Timestamp (UTC): ' + payload.timestamp_utc
    ].join('\n');
  }

  function setStatusTone(text) {
    var box = qs('#installStatus');
    if (!box) return;

    var t = (text || '').toLowerCase();
    var tone = 'warn';

    if (
      t.includes('ready') ||
      t.includes('installed') ||
      t.includes('standalone') ||
      t.includes('active') ||
      t.includes('success')
    ) {
      tone = 'ok';
    } else if (
      t.includes('not supported') ||
      t.includes('error') ||
      t.includes('failed') ||
      t.includes('missing')
    ) {
      tone = 'bad';
    }

    box.setAttribute('data-tone', tone);
  }

  async function refreshSummaryPanels() {
    setText('igDiagStandalone', isStandalone() ? 'Running as app' : 'Browser tab');
    setText('igDiagOnline', navigator.onLine ? 'Online' : 'Offline');
    setText('igDiagPrompt', getInstallPromptState());
    setText('igDiagSW', await getSWState());

    var payload = await buildDiagnosticsPayload();
    var diagBox = qs('#diagBox');
    if (diagBox) {
      diagBox.textContent = formatPayload(payload);
    }

    var installMeta = qs('#installMeta');
    if (installMeta) {
      installMeta.textContent = 'Last diagnostics update: ' + payload.timestamp_utc;
    }

    var status = qs('#installStatus');
    if (status) {
      var message = 'Install prompt not yet available. Open the main app and let the browser finish evaluating install eligibility.';
      if (payload.standalone_mode) {
        message = 'App is already running in standalone mode.';
      } else if (payload.install_prompt_available === 'Available') {
        message = 'Ready to install from this browser session.';
      } else if (!payload.online) {
        message = 'You are offline. Cached pages may still work, but install availability may be limited.';
      }
      status.textContent = message;
      setStatusTone(message);
    }
  }

  async function copyDiagnostics() {
    var payload = await buildDiagnosticsPayload();
    var text = formatPayload(payload);

    try {
      await navigator.clipboard.writeText(text);
      var meta = qs('#installMeta');
      if (meta) {
        meta.textContent = 'Diagnostics copied at ' + payload.timestamp_utc;
      }
    } catch (_) {
      var meta = qs('#installMeta');
      if (meta) {
        meta.textContent = 'Copy failed. Select and copy the report manually.';
      }
    }
  }

  function hookCopyButton() {
    var btn = qs('#btnCopyDebug');
    if (!btn || btn.dataset.igDiagBound === '1') return;
    btn.dataset.igDiagBound = '1';
    btn.addEventListener('click', function () {
      copyDiagnostics();
    });
  }

  function hookRefreshButtonFeedback() {
    var btn = qs('#btnSwRefresh');
    if (!btn || btn.dataset.igSwBound === '1') return;
    btn.dataset.igSwBound = '1';

    btn.addEventListener('click', function () {
      var status = qs('#installStatus');
      if (status) {
        status.textContent = 'Service worker refresh requested. Wait briefly, then refresh the main app if needed.';
        setStatusTone('success');
      }
      setTimeout(refreshSummaryPanels, 500);
      setTimeout(refreshSummaryPanels, 1200);
    });
  }

  function hookCheckButtonFeedback() {
    var btn = qs('#btnCheck');
    if (!btn || btn.dataset.igCheckBound === '1') return;
    btn.dataset.igCheckBound = '1';

    btn.addEventListener('click', function () {
      setTimeout(refreshSummaryPanels, 100);
      setTimeout(refreshSummaryPanels, 500);
    });
  }

  function bindEvents() {
    hookCopyButton();
    hookRefreshButtonFeedback();
    hookCheckButtonFeedback();

    window.addEventListener('online', refreshSummaryPanels);
    window.addEventListener('offline', refreshSummaryPanels);
    window.addEventListener('igcal:install-available', refreshSummaryPanels);
    window.addEventListener('igcal:install-state-changed', refreshSummaryPanels);
    window.addEventListener('igcal:installed', refreshSummaryPanels);
    window.addEventListener('igcal:update-available', refreshSummaryPanels);
    window.addEventListener('igcal:display-mode-change', refreshSummaryPanels);
  }

  function boot() {
    bindEvents();
    refreshSummaryPanels();
    setTimeout(refreshSummaryPanels, 300);
    setTimeout(refreshSummaryPanels, 900);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
</script>

<?php
if (function_exists('mk_require_shared')) {
    try {
        mk_require_shared('public_footer.php');
    } catch (Throwable $e) {
        echo "</body></html>";
    }
} else {
    echo "</body></html>";
}