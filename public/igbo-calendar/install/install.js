/* /public/igbo-calendar/install/install.js */
(() => {
  "use strict";

  const $ = (id) => document.getElementById(id);

  const btnInstall  = $("btnInstall");
  const btnCheck    = $("btnCheck");
  const btnCopy     = $("btnCopyDebug");
  const statusBox   = $("installStatus");
  const diagBox     = $("diagBox");

  if (!statusBox) return;

  const cfg = window.__MK_PWA || {};
  const BC_NAME = "mk_pwa_install";

  const ENDPOINTS = {
    appUrl:      cfg.appUrl      || "/igbo-calendar/?pwa=1",
    manifestUrl: cfg.manifestUrl || "/igbo-calendar/manifest.json",
    swUrl:       cfg.swUrl       || "/igbo-calendar/service-worker.js",
    scope:       cfg.scope       || "/igbo-calendar/",
    installPage: "/igbo-calendar/install/"
  };

  let deferredPrompt = null;     // Only valid on THIS page/tab
  let lastDiag = null;

  function setStatus(html) {
    try { statusBox.innerHTML = html; } catch (_) {}
  }

  function setDiag(obj) {
    lastDiag = obj;
    if (!diagBox) return;
    try { diagBox.textContent = JSON.stringify(obj, null, 2); } catch (_) {}
  }

  function safeUA() {
    try { return navigator.userAgent || ""; } catch (_) { return ""; }
  }

  function nowIso() {
    try { return new Date().toISOString(); } catch (_) { return ""; }
  }

  function isStandaloneDisplayMode() {
    try { return window.matchMedia("(display-mode: standalone)").matches; }
    catch (_) { return false; }
  }

  function isIOS() {
    const ua = safeUA();
    return /iPad|iPhone|iPod/.test(ua) && !("MSStream" in window);
  }

  function isEdge() {
    return safeUA().includes("Edg/");
  }

  function isChromium() {
    const ua = safeUA();
    return !!window.chrome || ua.includes("Chromium") || ua.includes("Chrome/");
  }

  async function headOk(url) {
    try {
      const res = await fetch(url, { method: "HEAD", cache: "no-store" });
      return { ok: res.ok, status: res.status, type: "HEAD" };
    } catch (e) {
      return { ok: false, status: 0, type: "HEAD", error: String(e && e.message ? e.message : e) };
    }
  }

  async function getInstalledRelatedAppsSafe() {
    // Chromium-only, best-effort
    try {
      if (navigator.getInstalledRelatedApps) {
        const apps = await navigator.getInstalledRelatedApps();
        return Array.isArray(apps) ? apps : [];
      }
    } catch (_) {}
    return null;
  }

  function platformHints() {
    return {
      isIOS: isIOS(),
      isEdge: isEdge(),
      isChromium: isChromium(),
      standaloneDisplayMode: isStandaloneDisplayMode(),
      isSecureContext: !!window.isSecureContext,
      controller: !!(navigator.serviceWorker && navigator.serviceWorker.controller),
      beforeinstallpromptFired: false
    };
  }

  function installHelpHtml() {
    // “Deep link” to native install UI is not supported; we give precise steps instead.
    const browser = isEdge() ? "Edge" : (isChromium() ? "Chrome" : "your browser");

    return (
      `<strong>Install help (${browser}):</strong><br>` +
      `1) Open the app page: <a class="btn btn--ghost" href="${ENDPOINTS.appUrl}">Open the App</a><br>` +
      `2) Refresh once (Ctrl+Shift+R) and click any UI element.<br>` +
      `3) Use the address bar install icon, or menu (⋯) → <strong>Install app</strong> / <strong>Apps</strong> → <strong>Install this site as an app</strong>.<br>` +
      `Note: browsers may not fire an install prompt on demand (that is normal).`
    );
  }

  function setInstallButtonMode(mode) {
    if (!btnInstall) return;

    // Modes:
    // - "prompt": we have a deferredPrompt and can prompt
    // - "help": no prompt; button opens help instructions
    // - "installed": already installed
    if (mode === "installed") {
      btnInstall.disabled = true;
      btnInstall.textContent = "Installed";
      return;
    }
    if (mode === "prompt") {
      btnInstall.disabled = false;
      btnInstall.textContent = "Install App";
      return;
    }
    // help mode
    btnInstall.disabled = false;               // make it useful
    btnInstall.textContent = "How to Install"; // truthful
  }

  function listenForBroadcast() {
    try {
      const bc = new BroadcastChannel(BC_NAME);
      bc.onmessage = (ev) => {
        const data = ev && ev.data ? ev.data : {};
        if (data && (data.type === "bip" || data.type === "installed")) {
          runDiagnostics().catch(() => {});
        }
      };
    } catch (_) {}
  }

  async function ensureSWRegistered() {
    const out = {
      supported: ("serviceWorker" in navigator),
      registerAttempted: false,
      registerOk: false,
      readyOk: false,
      controller: !!(navigator.serviceWorker && navigator.serviceWorker.controller),
      scope: null,
      error: null
    };

    if (!out.supported) return out;

    try {
      out.registerAttempted = true;
      const reg = await navigator.serviceWorker.register(ENDPOINTS.swUrl, { scope: ENDPOINTS.scope });
      out.registerOk = true;
      out.scope = reg && reg.scope ? reg.scope : null;

      await navigator.serviceWorker.ready;
      out.readyOk = true;

      out.controller = !!navigator.serviceWorker.controller;
      return out;
    } catch (e) {
      out.error = String(e && e.message ? e.message : e);
      return out;
    }
  }

  async function getSWState() {
    const out = {
      ok: false,
      scope: null,
      active: false,
      waiting: false,
      installing: false,
      controller: !!(navigator.serviceWorker && navigator.serviceWorker.controller)
    };

    if (!("serviceWorker" in navigator)) return out;

    try {
      const reg = await navigator.serviceWorker.getRegistration(ENDPOINTS.scope);
      if (!reg) return out;

      out.ok = true;
      out.scope = reg.scope || null;
      out.active = !!reg.active;
      out.waiting = !!reg.waiting;
      out.installing = !!reg.installing;
      out.controller = !!navigator.serviceWorker.controller;
      return out;
    } catch (_) {
      return out;
    }
  }

  // Capture install prompt event (only when browser decides eligible)
  window.addEventListener("beforeinstallprompt", (e) => {
    try { e.preventDefault(); } catch (_) {}
    deferredPrompt = e;

    const hints = platformHints();
    hints.beforeinstallpromptFired = true;

    setInstallButtonMode("prompt");
    setStatus("Install is available on this page. Click <strong>Install App</strong>.");

    // Hint other tabs (cannot pass the prompt itself)
    try {
      const bc = new BroadcastChannel(BC_NAME);
      bc.postMessage({ type: "bip", ts: Date.now() });
      bc.close();
    } catch (_) {}
  });

  window.addEventListener("appinstalled", () => {
    deferredPrompt = null;
    setInstallButtonMode("installed");
    setStatus("Installed successfully.");

    try {
      const bc = new BroadcastChannel(BC_NAME);
      bc.postMessage({ type: "installed", ts: Date.now() });
      bc.close();
    } catch (_) {}
  });

  async function runDiagnostics() {
    const hints = platformHints();

    const installedRelated = await getInstalledRelatedAppsSafe();

    const swEnsure = await ensureSWRegistered();
    const reach = {
      manifest: await headOk(ENDPOINTS.manifestUrl),
      serviceWorker: await headOk(ENDPOINTS.swUrl)
    };
    const sw = await getSWState();

    let manifestLink = null;
    try { manifestLink = document.querySelector('link[rel="manifest"]'); } catch (_) {}

    const diag = {
      ts: nowIso(),
      location: { href: location.href, pathname: location.pathname },
      ua: safeUA(),
      platformHints: hints,
      supports: {
        serviceWorker: ("serviceWorker" in navigator),
        fetch: ("fetch" in window),
        broadcastChannel: ("BroadcastChannel" in window),
        clipboard: !!(navigator.clipboard && navigator.clipboard.writeText),
        getInstalledRelatedApps: !!navigator.getInstalledRelatedApps
      },
      endpoints: {
        app: ENDPOINTS.appUrl,
        manifest: ENDPOINTS.manifestUrl,
        serviceWorker: ENDPOINTS.swUrl,
        scope: ENDPOINTS.scope,
        installPage: ENDPOINTS.installPage
      },
      document: {
        manifestLinkPresent: !!manifestLink,
        manifestHref: manifestLink ? (manifestLink.getAttribute("href") || "") : ""
      },
      reachability: reach,
      serviceWorkerEnsure: swEnsure,
      serviceWorker: sw,
      installedRelatedApps: installedRelated,
      install: {
        hasDeferredPrompt: !!deferredPrompt
      },
      errors: []
    };

    if (!window.isSecureContext) diag.errors.push("Not a secure context. PWA install requires HTTPS (or localhost).");
    if (!reach.manifest.ok) diag.errors.push("Manifest is not reachable (HEAD failed).");
    if (!reach.serviceWorker.ok) diag.errors.push("Service worker script is not reachable (HEAD failed).");
    if (!diag.document.manifestLinkPresent) diag.errors.push("No <link rel=\"manifest\"> found on this page.");

    setDiag(diag);

    // Truthful UI decisions
    if (hints.isIOS) {
      setInstallButtonMode("help");
      setStatus("iOS: install from Safari → Share → <strong>Add to Home Screen</strong>.");
      return diag;
    }

    // Installed detection (multiple signals)
    const alreadyInstalled =
      hints.standaloneDisplayMode ||
      (window.navigator && window.navigator.standalone === true) ||
      (Array.isArray(installedRelated) && installedRelated.length > 0);

    if (alreadyInstalled) {
      setInstallButtonMode("installed");
      setStatus("This app is already installed (standalone mode detected).");
      return diag;
    }

    // If prompt exists here, we can actually prompt
    if (deferredPrompt) {
      setInstallButtonMode("prompt");
      setStatus("Install is available. Click <strong>Install App</strong>.");
      return diag;
    }

    // Ensure SW control guidance (common first-visit behavior)
    if ("serviceWorker" in navigator && !navigator.serviceWorker.controller) {
      setInstallButtonMode("help");
      setStatus("Service worker registered, but it is not controlling this page yet. Reload once, then run <strong>Install Check</strong> again.");
      return diag;
    }

    // Browser gating: button becomes a helpful “How to Install”
    setInstallButtonMode("help");
    setStatus(installHelpHtml());
    return diag;
  }

  async function doInstallOrHelp() {
    if (deferredPrompt) {
      try {
        setStatus("Showing install prompt…");
        await deferredPrompt.prompt();

        let choice = null;
        try { choice = await deferredPrompt.userChoice; } catch (_) {}

        deferredPrompt = null;

        if (choice && choice.outcome) {
          if (choice.outcome === "accepted") {
            setStatus("Install accepted. If nothing happens, check your browser’s install UI/menu.");
          } else {
            setStatus("Install dismissed. You can try again later (browser may delay the prompt).");
          }
        } else {
          setStatus("Install prompt requested. If nothing happens, use the browser menu → Install app.");
        }
      } catch (_) {
        deferredPrompt = null;
        setInstallButtonMode("help");
        setStatus(installHelpHtml());
      }
      return;
    }

    // No prompt → help mode (truthful)
    setInstallButtonMode("help");
    setStatus(installHelpHtml());

    // Optional: open the app page in a new tab for convenience
    try { window.open(ENDPOINTS.appUrl, "_blank", "noopener"); } catch (_) {}
  }

  async function copyDebug() {
    const txt = lastDiag ? JSON.stringify(lastDiag, null, 2) : "No diagnostics captured yet.";
    try {
      await navigator.clipboard.writeText(txt);
      setStatus("Debug info copied.");
      return;
    } catch (_) {}

    try {
      const ta = document.createElement("textarea");
      ta.value = txt;
      ta.style.position = "fixed";
      ta.style.left = "-9999px";
      document.body.appendChild(ta);
      ta.focus();
      ta.select();
      document.execCommand("copy");
      document.body.removeChild(ta);
      setStatus("Debug info copied.");
    } catch (_) {
      setStatus("Could not copy debug in this browser. Copy from the Diagnostics box manually.");
    }
  }

  // Wire buttons
  if (btnInstall) btnInstall.addEventListener("click", () => { doInstallOrHelp().catch(() => {}); });
  if (btnCheck) btnCheck.addEventListener("click", () => { runDiagnostics().catch(() => {}); });
  if (btnCopy) btnCopy.addEventListener("click", () => { copyDebug().catch(() => {}); });

  // Init
  listenForBroadcast();

  // Default state: be useful, not greyed out.
  setInstallButtonMode("help");
  runDiagnostics().catch(() => {
    setStatus("Diagnostics failed to run.");
    setDiag({ ok: false, error: "Diagnostics exception", ts: nowIso() });
  });
})();
