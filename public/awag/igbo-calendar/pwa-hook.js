/* /public/igbo-calendar/pwa-hook.js
 * Registers the authoritative service worker,
 * captures install prompt,
 * and exposes app-shell state to the UI.
 */
(function () {
  'use strict';

  var SW_URL = '/igbo-calendar/service-worker.js';
  var SW_SCOPE = '/igbo-calendar/';
  var deferredPrompt = null;
  var root = document.documentElement;

  function isStandaloneMode() {
    try {
      return !!(
        (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
        window.navigator.standalone === true
      );
    } catch (_) {
      return false;
    }
  }

  function setFlag(name, on) {
    if (!root) return;
    root.classList.toggle(name, !!on);
    root.setAttribute('data-' + name, on ? '1' : '0');
  }

  function refreshAppState() {
    setFlag('igcal-standalone', isStandaloneMode());
    setFlag('igcal-online', navigator.onLine);
    setFlag('igcal-offline', !navigator.onLine);
    setFlag('igcal-can-install', !!deferredPrompt);
  }

  function hideInstallEntrypointsWhenStandalone() {
    if (!isStandaloneMode()) return;

    var selectors = [
      'a[href="/igbo-calendar/install/"]',
      'a[href="/igbo-calendar/install"]',
      'a[href$="/igbo-calendar/install/"]',
      'a[href$="/igbo-calendar/install"]',
      '#btnInstall',
      '[data-role="install-link"]',
      '[data-role="install-button"]'
    ];

    selectors.forEach(function (sel) {
      try {
        var nodes = document.querySelectorAll(sel);
        nodes.forEach(function (el) {
          el.style.display = 'none';
          el.setAttribute('hidden', 'hidden');
          el.setAttribute('aria-hidden', 'true');
        });
      } catch (_) {}
    });
  }

  function notify(name, detail) {
    try {
      window.dispatchEvent(new CustomEvent(name, { detail: detail || {} }));
    } catch (_) {}
  }

  window.igcalCanInstall = function () {
    return !!deferredPrompt;
  };

  window.igcalPromptInstall = async function () {
    if (!deferredPrompt) return false;

    try {
      deferredPrompt.prompt();

      if (deferredPrompt.userChoice && typeof deferredPrompt.userChoice.then === 'function') {
        deferredPrompt.userChoice.then(function () {
          deferredPrompt = null;
          refreshAppState();
          notify('igcal:install-state-changed');
        }).catch(function () {
          deferredPrompt = null;
          refreshAppState();
          notify('igcal:install-state-changed');
        });
      } else {
        deferredPrompt = null;
        refreshAppState();
        notify('igcal:install-state-changed');
      }

      return true;
    } catch (_) {
      deferredPrompt = null;
      refreshAppState();
      notify('igcal:install-state-changed');
      return false;
    }
  };

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    refreshAppState();
    notify('igcal:install-available');
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    refreshAppState();
    hideInstallEntrypointsWhenStandalone();
    notify('igcal:installed');
  });

  window.addEventListener('online', function () {
    refreshAppState();
    notify('igcal:online');
    try {
      if (navigator.serviceWorker && navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({ type: 'REFRESH_CORE' });
      }
    } catch (_) {}
  });

  window.addEventListener('offline', function () {
    refreshAppState();
    notify('igcal:offline');
  });

  try {
    if (window.matchMedia) {
      var mm = window.matchMedia('(display-mode: standalone)');
      if (mm && typeof mm.addEventListener === 'function') {
        mm.addEventListener('change', function () {
          refreshAppState();
          hideInstallEntrypointsWhenStandalone();
          notify('igcal:display-mode-change');
        });
      } else if (mm && typeof mm.addListener === 'function') {
        mm.addListener(function () {
          refreshAppState();
          hideInstallEntrypointsWhenStandalone();
          notify('igcal:display-mode-change');
        });
      }
    }
  } catch (_) {}

  function registerSW() {
    if (!('serviceWorker' in navigator)) return;

    navigator.serviceWorker.register(SW_URL, {
      scope: SW_SCOPE,
      updateViaCache: 'none'
    }).then(function (reg) {
      try { reg.update(); } catch (_) {}

      if (reg.waiting) {
        notify('igcal:update-available');
      }

      reg.addEventListener('updatefound', function () {
        var sw = reg.installing;
        if (!sw) return;

        sw.addEventListener('statechange', function () {
          if (sw.state === 'installed' && navigator.serviceWorker.controller) {
            notify('igcal:update-available');
          }
        });
      });
    }).catch(function () {
      /* silent */
    });

    navigator.serviceWorker.addEventListener('controllerchange', function () {
      notify('igcal:sw-controller-changed');
    });
  }

  function boot() {
    refreshAppState();
    hideInstallEntrypointsWhenStandalone();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }

  window.addEventListener('load', function () {
    refreshAppState();
    hideInstallEntrypointsWhenStandalone();
    registerSW();
  });
})();