/* /public/igbo-calendar/install/install.js */
(() => {
  "use strict";

  const $ = (id) => document.getElementById(id);

  const btnInstall   = $("btnInstall");
  const btnCheck     = $("btnCheck");
  const btnCopyDebug = $("btnCopyDebug");
  const btnSwRefresh = $("btnSwRefresh");
  const statusBox    = $("installStatus");
  const metaBox      = $("installMeta");
  const diagBox      = $("diagBox");

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

  let deferredPrompt = null;
  let lastDiag = null;

  function setStatus(html) {
    try { statusBox.innerHTML = html; } catch (_) {}
  }

  function setMeta(text) {
    if (!metaBox) return;
    try { metaBox.textContent = text; } catch (_) {}
  }

  function setDiag(obj) {
    lastDiag = obj;
    if (!diagBox) return;
    try {
      diagBox.textContent = JSON.stringify(obj, null, 2);
    } catch (_) {
      diagBox.textContent = String(obj || "");
    }
  }

  function safeUA() {
    try { return navigator.userAgent || ""; } catch (_) { return ""; }
  }

  function nowIso() {
    try { return new Date().toISOString(); } catch (_) { return ""; }
  }

  function nowLocalLabel() {
    try {
      return new Date().toLocaleString();
    } catch (_) {
      return nowIso();
    }
  }

  function isStandaloneDisplayMode() {
    try {
      return !!(window.matchMedia && window.matchMedia("(display-mode: standalone)").matches);
    } catch (_) {
      return false;
    }
  }

  function isIOS() {
    const ua = safeUA();
    return /iPad|iPhone|iPod/.test(ua) && !("MSStream" in window);
  }

  function isAndroid() {
    return /Android/i.test(safeUA());
  }

  function isEdge() {
    return safeUA().includes("Edg/");
  }

  function isChromium() {
    const ua = safeUA();
    return !!window.chrome || ua.includes("Chromium") || ua.includes("Chrome/");
  }

  function bumpButton(btn, tempText, ms = 1800) {
    if (!btn) return;
    const original = btn.getAttribute("data-orig-text") || btn.textContent || "";
    btn.setAttribute("data-orig-text", original);
    btn.textContent = tempText;
    window.setTimeout(() => {
      try {
        btn.textContent = btn.getAttribute("data-orig-text") || original;
      } catch (_) {}
    }, ms);
  }

  async function headOk(url) {
    try {
      const res = await fetch(url, { method: "HEAD", cache: "no-store" });
      return {
        ok: !!res.ok,
        status: res.status,
        contentType: res.headers.get("content-type") || ""
      };
    } catch (e) {
      return {
        ok: false,
        status: 0,
        error: String(e && e.message ? e.message : e)
      };
    }
  }

  async function getInstalledRelatedAppsSafe() {
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
      isAndroid: isAndroid(),
      isEdge: isEdge(),
      isChromium: isChromium(),
      standaloneDisplayMode: isStandaloneDisplayMode(),
      navigatorStandalone: !!(window.navigator && window.navigator.standalone === true),
      online: !!navigator.onLine,
      secureContext: !!window.isSecureContext
    };
  }

  function helpHtml() {
    if (isIOS()) {
      return (
        "<strong>Install help:</strong><br>" +
        "Open the main app page in Safari, then use <strong>Share → Add to Home Screen</strong>.<br>" +
        "This diagnostics page is not the primary install target."
      );
    }

    const browser = isEdge() ? "Edge" : (isChromium() ? "Chrome" : "your browser");

    return (
      "<strong>Install help (" + browser + "):</strong><br>" +
      '1) Open <a class="btn btn--ghost" href="' + ENDPOINTS.appUrl + '">Open the App</a><br>' +
      "2) Refresh once and interact with the main app page.<br>" +
      "3) Use the browser menu or address-bar install icon.<br>" +
      "Note: this page is for diagnostics and support; the main app remains the preferred install surface."
    );
  }

  function setInstallButtonMode(mode) {
    if (!btnInstall) return;

    if (mode === "installed") {
      btnInstall.disabled = true;
      btnInstall.textContent = "Installed";
      btnInstall.setAttribute("data-orig-text", "Installed");
      return;
    }

    if (mode === "prompt") {
      btnInstall.disabled = false;
      btnInstall.textContent = "Install App";
      btnInstall.setAttribute("data-orig-text", "Install App");
      return;
    }

    btnInstall.disabled = false;
    btnInstall.textContent = "How to Install";
    btnInstall.setAttribute("data-orig-text", "How to Install");
  }

  function listenForBroadcast() {
    try {
      const bc = new BroadcastChannel(BC_NAME);
      bc.onmessage = (ev) => {
        const data = ev && ev.data ? ev.data : {};
        if (data.type === "bip" || data.type === "installed" || data.type === "sw-updated") {
          runDiagnostics({ silent: true }).catch(() => {});
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
      error: null,
      existing: false
    };

    if (!out.supported) return out;

    try {
      const existing = await navigator.serviceWorker.getRegistration(ENDPOINTS.scope);

      if (existing) {
        out.existing = true;
        out.registerOk = true;
        out.scope = existing && existing.scope ? existing.scope : null;
        await navigator.serviceWorker.ready;
        out.readyOk = true;
        out.controller = !!navigator.serviceWorker.controller;
        return out;
      }

      out.registerAttempted = true;

      const reg = await navigator.serviceWorker.register(ENDPOINTS.swUrl, {
        scope: ENDPOINTS.scope,
        updateViaCache: "none"
      });

      out.registerOk = true;
      out.scope = reg && reg.scope ? reg.scope : null;

      try { await reg.update(); } catch (_) {}

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

  async function refreshServiceWorker() {
    if (!("serviceWorker" in navigator)) {
      setStatus("Service workers are not supported in this browser.");
      bumpButton(btnSwRefresh, "Unavailable", 1800);
      return;
    }

    setStatus("Requesting service worker refresh…");

    try {
      const reg = await navigator.serviceWorker.getRegistration(ENDPOINTS.scope);

      if (!reg) {
        setStatus("No service worker registration found for the app scope. Open the main app once, then run diagnostics again.");
        bumpButton(btnSwRefresh, "No SW", 1800);
        return;
      }

      try { await reg.update(); } catch (_) {}

      const targets = [reg.waiting, reg.installing, reg.active].filter(Boolean);
      targets.forEach((sw) => {
        try { sw.postMessage({ type: "SKIP_WAITING" }); } catch (_) {}
      });

      try {
        const bc = new BroadcastChannel(BC_NAME);
        bc.postMessage({ type: "sw-updated", ts: Date.now() });
        bc.close();
      } catch (_) {}

      setStatus(
        'Service worker refresh requested. Open <a class="btn btn--ghost" href="' +
        ENDPOINTS.appUrl +
        '">the main app</a> and refresh once.'
      );
      bumpButton(btnSwRefresh, "Requested", 1800);

      window.setTimeout(() => {
        runDiagnostics({ silent: true }).catch(() => {});
      }, 600);

      window.setTimeout(() => {
        runDiagnostics({ silent: true }).catch(() => {});
      }, 1400);
    } catch (_) {
      setStatus("Could not refresh the service worker automatically. Open the main app page and refresh once.");
      bumpButton(btnSwRefresh, "Failed", 1800);
    }
  }

  window.addEventListener("beforeinstallprompt", (e) => {
    try { e.preventDefault(); } catch (_) {}
    deferredPrompt = e;

    setInstallButtonMode("prompt");
    setStatus("Install is available. Click <strong>Install App</strong>.");

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

  async function runDiagnostics(opts = {}) {
    const silent = !!opts.silent;

    const hints = platformHints();
    const installedRelated = await getInstalledRelatedAppsSafe();
    const swEnsure = await ensureSWRegistered();
    const sw = await getSWState();

    const reach = {
      manifest: await headOk(ENDPOINTS.manifestUrl),
      serviceWorker: await headOk(ENDPOINTS.swUrl),
      app: await headOk(ENDPOINTS.appUrl)
    };

    let manifestLinkHref = null;
    try {
      const manifestLink = document.querySelector('link[rel="manifest"]');
      manifestLinkHref = manifestLink ? manifestLink.getAttribute("href") : null;
    } catch (_) {}

    const diag = {
      ts: nowIso(),
      location: {
        href: location.href,
        pathname: location.pathname
      },
      ua: safeUA(),
      platformHints: hints,
      supports: {
        serviceWorker: ("serviceWorker" in navigator),
        fetch: ("fetch" in window),
        broadcastChannel: ("BroadcastChannel" in window),
        clipboard: !!(navigator.clipboard && navigator.clipboard.writeText),
        getInstalledRelatedApps: !!navigator.getInstalledRelatedApps
      },
      endpoints: ENDPOINTS,
      pageManifestHref: manifestLinkHref,
      reachability: reach,
      serviceWorkerRegistration: swEnsure,
      serviceWorkerState: sw,
      installedRelatedApps: installedRelated,
      deferredPromptAvailable: !!deferredPrompt
    };

    setDiag(diag);
    setMeta("Last diagnostics update: " + nowLocalLabel());

    if (hints.standaloneDisplayMode || hints.navigatorStandalone) {
      setInstallButtonMode("installed");
      if (!silent) setStatus("This page is already running in installed app mode.");
      return diag;
    }

    if (deferredPrompt) {
      setInstallButtonMode("prompt");
      if (!silent) setStatus("Install is available. Click <strong>Install App</strong>.");
      return diag;
    }

    setInstallButtonMode("help");

    if (isIOS()) {
      if (!silent) setStatus(helpHtml());
      return diag;
    }

    if (!reach.manifest.ok || !reach.serviceWorker.ok) {
      if (!silent) {
        setStatus("Install requirements are not fully reachable yet. Refresh the main app page, then run this check again.");
      }
      return diag;
    }

    if (!silent) {
      setStatus(helpHtml());
    }

    return diag;
  }

  async function promptInstall() {
    if (!btnInstall) return;

    if (!deferredPrompt) {
      setStatus(helpHtml());
      bumpButton(btnInstall, "See Help", 1800);
      return;
    }

    try {
      await deferredPrompt.prompt();

      if (deferredPrompt.userChoice) {
        const choice = await deferredPrompt.userChoice;
        if (choice && choice.outcome === "accepted") {
          setStatus("Install accepted. Finishing…");
        } else {
          setStatus("Install prompt was dismissed.");
        }
      }
    } catch (_) {
      setStatus("Could not open the install prompt. Use the browser install menu instead.");
    } finally {
      deferredPrompt = null;
      runDiagnostics().catch(() => {});
    }
  }

  async function copyDebug() {
    const text = JSON.stringify(lastDiag || {}, null, 2);

    if (!text || text === "{}") {
      setStatus("No diagnostics available yet. Run <strong>Run Install Check</strong> first.");
      bumpButton(btnCopyDebug, "No Data", 1800);
      return;
    }

    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(text);
        setStatus("Diagnostics copied. Paste into Notepad or any text box with Ctrl+V.");
        bumpButton(btnCopyDebug, "Copied", 2200);
        return;
      }
    } catch (_) {}

    try {
      const ta = document.createElement("textarea");
      ta.value = text;
      ta.setAttribute("readonly", "readonly");
      ta.style.position = "fixed";
      ta.style.left = "-9999px";
      ta.style.top = "0";
      document.body.appendChild(ta);
      ta.focus();
      ta.select();
      const ok = document.execCommand("copy");
      document.body.removeChild(ta);

      if (ok) {
        setStatus("Diagnostics copied. Paste into Notepad or any text box with Ctrl+V.");
        bumpButton(btnCopyDebug, "Copied", 2200);
        return;
      }

      throw new Error("copy failed");
    } catch (_) {
      setStatus("Could not copy automatically. Select and copy the diagnostics block manually.");
      bumpButton(btnCopyDebug, "Failed", 2200);
    }
  }

  if (btnInstall) {
    btnInstall.addEventListener("click", (e) => {
      e.preventDefault();
      promptInstall().catch(() => {
        setStatus("Could not open the install prompt. Use the browser install menu instead.");
      });
    });
  }

  if (btnCheck) {
    btnCheck.addEventListener("click", (e) => {
      e.preventDefault();
      setStatus("Running install check…");
      runDiagnostics().then(() => {
        setStatus("Diagnostics updated. Review the status above and the JSON block below.");
        bumpButton(btnCheck, "Checked", 1800);
      }).catch(() => {
        setStatus("Install diagnostics failed.");
        bumpButton(btnCheck, "Failed", 1800);
      });
    });
  }

  if (btnCopyDebug) {
    btnCopyDebug.addEventListener("click", (e) => {
      e.preventDefault();
      copyDebug().catch(() => {
        setStatus("Could not copy diagnostics.");
        bumpButton(btnCopyDebug, "Failed", 2200);
      });
    });
  }

  if (btnSwRefresh) {
    btnSwRefresh.addEventListener("click", (e) => {
      e.preventDefault();
      refreshServiceWorker().catch(() => {
        setStatus("Could not refresh the service worker.");
        bumpButton(btnSwRefresh, "Failed", 1800);
      });
    });
  }

  listenForBroadcast();
  setStatus("Checking install support…");
  setMeta("Last diagnostics update: running…");
  runDiagnostics().catch(() => {
    setStatus("Install diagnostics failed.");
    setMeta("Last diagnostics update: failed.");
  });
})();