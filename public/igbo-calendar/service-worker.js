/* /public/igbo-calendar/service-worker.js */
'use strict';

/**
 * Igbo Calendar Service Worker (Install-grade PWA)
 *
 * Goals:
 * - Deterministic install eligibility (stable SW lifecycle)
 * - Offline support for the app shell + core assets
 * - Safe updates (versioned cache, skipWaiting, clients.claim)
 *
 * Strategy:
 * - Navigations (HTML): network-first, cache fallback, then app-shell fallback
 * - Same-origin static assets: stale-while-revalidate
 * - Never cache dynamic endpoints (e.g., export download)
 */

const CACHE_VERSION = 'v17'; // bump when you change CORE_ASSETS or caching logic
const CACHE_NAME = `igbo-calendar-${CACHE_VERSION}`;

/**
 * IMPORTANT:
 * - Only include URLs that are stable and exist.
 * - Keep list small to avoid confusion during install debugging.
 */
const CORE_ASSETS = [
  '/igbo-calendar/',
  '/igbo-calendar/offline.html',

  '/igbo-calendar/manifest.json',
  '/igbo-calendar/igbo-calendar.css',
  '/lib/css/ui.css',

  // install helper (instructions) + scripts
  '/igbo-calendar/install/',
  '/igbo-calendar/install/install.css',
  '/igbo-calendar/install/install.js',

  // app hook (SW control on the app page)
  '/igbo-calendar/pwa-hook.js',

  // icons that you confirmed exist and match sizes
  '/igbo-calendar/icons/icon-192.png',
  '/igbo-calendar/icons/icon-512.png'
];

function isSameOrigin(url) {
  return url.origin === self.location.origin;
}

function isNavigationRequest(req) {
  if (req.mode === 'navigate') return true;
  const accept = (req.headers.get('accept') || '').toLowerCase();
  return accept.includes('text/html');
}

function isBypassPath(url) {
  // Never cache exports or other dynamic endpoints
  return (
    url.pathname.startsWith('/igbo-calendar/download/')
  );
}

function hasRangeHeader(req) {
  try { return !!req.headers.get('range'); } catch (_) { return false; }
}

async function safeCachePut(cache, request, response) {
  try {
    // Only cache successful same-origin basic responses
    if (response && response.ok && response.type === 'basic') {
      await cache.put(request, response.clone());
    }
  } catch (_) {}
}

async function precacheCore() {
  const cache = await caches.open(CACHE_NAME);

  // Best-effort: failures must not brick install
  await Promise.allSettled(
    CORE_ASSETS.map(async (path) => {
      const req = new Request(path, { cache: 'reload' });
      const res = await fetch(req);
      if (!res || !res.ok) throw new Error(`precache_failed: ${path} status=${res && res.status}`);
      await safeCachePut(cache, req, res);
      return true;
    })
  );
}

/* ---------------------------------------------------------
   Install: precache core + activate immediately
--------------------------------------------------------- */
self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    await precacheCore();
    await self.skipWaiting();
  })());
});

/* ---------------------------------------------------------
   Activate: clean old caches + take control immediately
--------------------------------------------------------- */
self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.map((k) => (k === CACHE_NAME ? true : caches.delete(k))));
    await self.clients.claim();
  })());
});

/* ---------------------------------------------------------
   Fetch
--------------------------------------------------------- */
self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  // Range requests (often media) are best left to network
  if (hasRangeHeader(req)) return;

  const url = new URL(req.url);

  // Cross-origin: don't touch
  if (!isSameOrigin(url)) return;

  // Bypass dynamic endpoints entirely (no caching)
  if (isBypassPath(url)) return;

  // HTML navigations: network-first, fallback to cache, then app shell, then offline
  if (isNavigationRequest(req)) {
    event.respondWith((async () => {
      const cache = await caches.open(CACHE_NAME);

      try {
        const fresh = await fetch(req, { cache: 'no-store' });
        await safeCachePut(cache, req, fresh);
        return fresh;
      } catch (_) {
        // 1) exact cached navigation
        const cached = await cache.match(req);
        if (cached) return cached;

        // 2) app shell (covers /igbo-calendar/ and /igbo-calendar/?pwa=1)
        const shell = await cache.match('/igbo-calendar/');
        if (shell) return shell;

        // 3) offline page
        const offline = await cache.match('/igbo-calendar/offline.html');
        if (offline) return offline;

        return new Response('Offline', {
          status: 503,
          headers: { 'Content-Type': 'text/plain; charset=utf-8' }
        });
      }
    })());
    return;
  }

  // Static assets: stale-while-revalidate
  event.respondWith((async () => {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(req);

    const fetchAndUpdate = (async () => {
      try {
        const fresh = await fetch(req);
        await safeCachePut(cache, req, fresh);
        return fresh;
      } catch (_) {
        return null;
      }
    })();

    if (cached) {
      fetchAndUpdate.catch(() => {});
      return cached;
    }

    const fresh = await fetchAndUpdate();
    if (fresh) return fresh;

    return new Response('Offline', {
      status: 503,
      headers: { 'Content-Type': 'text/plain; charset=utf-8' }
    });
  })());
});

/* ---------------------------------------------------------
   Messages (optional controls)
--------------------------------------------------------- */
self.addEventListener('message', (event) => {
  const data = event.data || {};
  if (data.type === 'SKIP_WAITING') {
    self.skipWaiting();
    return;
  }
  if (data.type === 'REFRESH_CORE') {
    event.waitUntil(precacheCore());
  }
});

<FilesMatch "(?:^|/)(?:service-worker|sw)\.(?:js|mjs)$">
  Header always set Cache-Control "no-cache, no-store, must-revalidate"
  Header always set Pragma "no-cache"
  Header always set Expires "0"
</FilesMatch>
