/* /public/igbo-calendar/install/pwa-hook.js */
(function () {
  'use strict';

  /*
   * This file is intentionally non-registering.
   *
   * Install prompting and authoritative service-worker registration
   * belong to:
   *   /igbo-calendar/pwa-hook.js
   *
   * The install helper page at /igbo-calendar/install/ is for:
   * - diagnostics
   * - status display
   * - refresh helpers
   *
   * It must not become a second service-worker registration owner.
   */

  var detail = {
    page: '/igbo-calendar/install/',
    role: 'diagnostics-only',
    serviceWorkerRegistrationOwner: '/igbo-calendar/pwa-hook.js'
  };

  try {
    window.__IGCAL_INSTALL_PWA_HOOK__ = detail;
  } catch (_) {}

  try {
    window.dispatchEvent(new CustomEvent('igcal:install-helper-hook-ready', { detail: detail }));
  } catch (_) {}
})();