/* /public/igbo-calendar/pwa-hook.js
 * Robust PWA hook for Igbo Calendar.
 *
 * Responsibilities:
 * - Register SW under /igbo-calendar/ scope (idempotent).
 * - Ensure controller becomes active (reload-once strategy).
 * - Provide truthful install UX hints (iOS / already-installed / browser gating).
 * - Optionally wire an install button if present (mkInstallBtn + mkInstallNote).
 * - Broadcast minimal “hint” events to other pages (install diagnostics) when useful.
 *
 * Safe to include on any page under /igbo-calendar/.
 */
(() => {
  "use strict";

  const SW_URL   = "/igbo-calendar/service-worker.js";
  const SCOPE    = "/igbo-calendar/";
  const RELOAD_KEY = "mk_sw_reload_once_v2";
  const BC_NAME = "mk_pwa_install";

  function log() {
    // Quiet by default; enable with window.__MK_PWA_DEBUG = true
    try {
      if (window.__MK_PWA_DEBUG) console.log.apply(console, arguments);
    } catch (_) {}
  }

  function $(id) { return document.getElementById(id); }

  function setText(el, text) {
    if (!el) return;
    try { el.textContent = String(text || ""); } catch (_) {}
  }

  function safeUA() {
    try { return String(navigator.userAgent || ""); } catch (_) { return ""; }
  }

  function isStandalone() {
    try {
      return (
        (window.matchMedia && window.matchMedia("(display-mode: standalone)").matches) ||
        (window.navigator && window.navigator.standalone === true)
      );
    } catch (_) {
      return false;
    }
  }

  function isIOS() {
    const ua = safeUA();
    return /iPad|iPhone|iPod/.test(ua) && !("MSStream" in window);
  }

  function isEdgeOrChrome() {
    const ua = safeUA();
    return /Edg\//.test(ua) || /Chrome\//.test(ua);
  }

  function inScope() {
    const p = (location && location.pathname) ? String(location.pathname) : "";
    return p.startsWith(SCOPE);
  }

  function getReloadedOnce() {
    try { return localStorage.getItem(RELOAD_KEY) === "1"; } catch (_) { return false; }
  }
  function setReloadedOnce() {
    try { localStorage.setItem(RELOAD_KEY, "1"); } catch (_) {}
  }
  function clearReloadedOnce() {
    try { localStorage.removeItem(RELOAD_KEY); } catch (_) {}
  }

  function broadcast(type) {
    try {
      if (!("BroadcastChannel" in window)) return;
      const bc = new BroadcastChannel(BC_NAME);
      bc.postMessage({ type, ts: Date.now(), path: location.pathname });
      bc.close();
    } catch (_) {}
  }

  // Optional UI elements (only exist on some pages)
  const btn  = $("mkInstallBtn");
  const note = $("mkInstallNote");

  let deferredPrompt = null;

  // Minimal debug snapshot
  const dbg = {
    ts: new Date().toISOString(),
    ua: safeUA(),
    path: (location && location.pathname) ? location.pathname : "",
    swSupported: ("serviceWorker" in navigator),
    inScope: false,
    registered: false,
    ready: false,
    controller: false,
    reloadedOnce: false,
    beforeinstallpromptFired: false,
    errors: []
  };
  try { window.__mkPwaHook = dbg; } catch (_) {}

  // Do nothing outside scope
  dbg.inScope = inScope();
  if (!dbg.inScope) {
    log("[PWA] outside scope:", dbg.path);
    return;
  }

  // Default UI
  if (btn) btn.style.display = "none";

  // Standalone / iOS guidance
  if (isStandalone()) {
    setText(note, "This app is already installed.");
    return;
  }
  if (isIOS()) {
    setText(note, "On iPhone/iPad: open in Safari → Share → Add to Home Screen.");
    return;
  }

  // Desktop/Android guidance
  if (isEdgeOrChrome()) {
    setText(note, "If install is available, an Install button will appear here. Otherwise use the browser menu (⋯) → Install app.");
  } else {
    setText(note, "Install support varies by browser. If you do not see install options, try Chrome or Edge.");
  }

  // If SW unsupported, stop here (install won’t happen)
  if (!("serviceWorker" in navigator)) {
    dbg.errors.push("serviceWorker not supported");
    return;
  }

  async function ensureServiceWorkerControl() {
    try {
      // Register SW (idempotent)
      const reg = await navigator.serviceWorker.register(SW_URL, {
        scope: SCOPE,
        updateViaCache: "none"
      });

      dbg.registered = true;
      log("[PWA] SW registered:", reg && reg.scope ? reg.scope : reg);

      // Ask browser to check for updates
      try { reg.update(); } catch (_) {}

      // If waiting SW exists, ask it to activate (SW may ignore)
      try { if (reg.waiting) reg.waiting.postMessage({ type: "SKIP_WAITING" }); } catch (_) {}

      // Wait for ready (active SW exists)
      await navigator.serviceWorker.ready;
      dbg.ready = true;

      // Controller is per-page and may require a reload on first install
      dbg.controller = !!navigator.serviceWorker.controller;

      if (!dbg.controller) {
        const alreadyReloaded = getReloadedOnce();
        dbg.reloadedOnce = alreadyReloaded;

        // Reload once to get controlled; avoid loops
        if (!alreadyReloaded) {
          setReloadedOnce();
          // Slight delay avoids tight loops and allows SW to settle
          setTimeout(() => {
            try { window.location.reload(); } catch (_) {}
          }, 300);
        } else {
          // Already reloaded once; do not loop.
          // At this point, control should normally exist; if not, something else is wrong.
          // Leave a breadcrumb for diagnostics.
          dbg.errors.push("SW not controlling after reload-once");
        }
      } else {
        // Once controlled, allow future reload-once after updates
        clearReloadedOnce();
      }

      return true;
    } catch (e) {
      dbg.errors.push(String(e && e.message ? e.message : e));
      log("[PWA] SW register failed:", e);
      return false;
    }
  }

  // Register SW early (don’t wait for load)
  // This increases the chance the *app page* becomes controlled quickly.
  ensureServiceWorkerControl().then((ok) => {
    if (ok && dbg.controller) broadcast("sw_controlled");
  });

  // Install prompt only exists in the same page/tab where it fires
  window.addEventListener("beforeinstallprompt", (e) => {
    try { e.preventDefault(); } catch (_) {}
    deferredPrompt = e;

    dbg.beforeinstallpromptFired = true;

    if (btn) btn.style.display = "inline-flex";
    setText(note, "Install is available. Click Install App.");

    broadcast("bip");
  });

  window.addEventListener("appinstalled", () => {
    deferredPrompt = null;

    if (btn) btn.style.display = "none";
    setText(note, "Installed successfully.");

    // Allow reload-once again after updates / re-installs
    clearReloadedOnce();

    broadcast("installed");
  });

  // Optional install button wiring
  if (btn) {
    btn.addEventListener("click", async () => {
      if (!deferredPrompt) {
        setText(note, "Install is not available right now. Use the browser menu (⋯) → Install app.");
        return;
      }

      try {
        await deferredPrompt.prompt();
        // userChoice is not universal; ignore failures
        try { if (deferredPrompt.userChoice) await deferredPrompt.userChoice; } catch (_) {}
      } catch (_) {}

      deferredPrompt = null;
      btn.style.display = "none";
    });
  }
})();
