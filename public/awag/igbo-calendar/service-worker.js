 /* /public/igbo-calendar/service-worker.js */
'use strict';

/**
 * Igbo Calendar Service Worker
 *
 * Goals:
 * - Stable PWA install eligibility
 * - Offline shell for /igbo-calendar/
 * - Safe cache versioning
 * - No caching of export/download endpoints
 */

const CACHE_VERSION = 'v25-awag';
const CACHE_PREFIX  = 'igbo-calendar-';
const CACHE_NAME    = `${CACHE_PREFIX}${CACHE_VERSION}`;

const APP_SHELL_PATH = '/awag/igbo-calendar/';
const OFFLINE_PATH   = '/awag/igbo-calendar/offline.php';

const CORE_ASSETS = [
  APP_SHELL_PATH,
  OFFLINE_PATH,
  '/awag/igbo-calendar/manifest.json',
  '/igbo-calendar/manifest.webmanifest',
  '/awag/igbo-calendar/igbo-calendar.css',
  '/awag/igbo-calendar/igbo-calendar.js',
  '/awag/igbo-calendar/calendar-ux.js',
  '/igbo-calendar/pwa-hook.js',
  '/igbo-calendar/install/',
  '/igbo-calendar/install/install.css',
  '/igbo-calendar/install/install.js',
  '/igbo-calendar/icons/icon-192.png',
  '/igbo-calendar/icons/icon-512.png'
];

function isSameOrigin(url) {
  return url.origin === self.location.origin;
}

function isNavigationRequest(req) {
  try {
    if (req.mode === 'navigate') return true;
    if (req.destination === 'document') return true;
  } catch (_) {}

  const accept = (req.headers.get('accept') || '').toLowerCase();
  return accept.includes('text/html');
}

function isBypassPath(url) {
  return (
    url.pathname.startsWith('/igbo-calendar/download/') ||
    url.pathname.startsWith('/staff/')
  );
}

function hasRangeHeader(req) {
  try {
    return !!req.headers.get('range');
  } catch (_) {
    return false;
  }
}

function isStaticAsset(url) {
  return (
    url.pathname.endsWith('.css')   ||
    url.pathname.endsWith('.js')    ||
    url.pathname.endsWith('.png')   ||
    url.pathname.endsWith('.jpg')   ||
    url.pathname.endsWith('.jpeg')  ||
    url.pathname.endsWith('.webp')  ||
    url.pathname.endsWith('.svg')   ||
    url.pathname.endsWith('.ico')   ||
    url.pathname.endsWith('.woff')  ||
    url.pathname.endsWith('.woff2') ||
    url.pathname.endsWith('.ttf')   ||
    url.pathname.endsWith('.json')  ||
    url.pathname.endsWith('.webmanifest')
  );
}

async function safeCachePut(cache, request, response) {
  try {
    if (!response) return;
    if (!response.ok) return;
    if (response.type !== 'basic') return;
    await cache.put(request, response.clone());
  } catch (_) {}
}

async function precacheCore() {
  const cache = await caches.open(CACHE_NAME);

  await Promise.allSettled(
    CORE_ASSETS.map(async (path) => {
      try {
        const req = new Request(path, { cache: 'reload' });
        const res = await fetch(req);
        await safeCachePut(cache, req, res);
      } catch (_) {}
    })
  );
}

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    await precacheCore();
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    try {
      const keys = await caches.keys();
      await Promise.all(
        keys.map((key) => {
          if (key === CACHE_NAME) return Promise.resolve(true);
          if (key.startsWith(CACHE_PREFIX)) return caches.delete(key);
          return Promise.resolve(true);
        })
      );
    } catch (_) {}

    await self.clients.claim();
  })());
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (!req || req.method !== 'GET') return;
  if (hasRangeHeader(req)) return;

  const url = new URL(req.url);

  if (!isSameOrigin(url)) return;
  if (isBypassPath(url)) return;

  if (isNavigationRequest(req)) {
    event.respondWith((async () => {
      const cache = await caches.open(CACHE_NAME);

      try {
        const fresh = await fetch(req);
        await safeCachePut(cache, req, fresh);

        if (url.pathname === APP_SHELL_PATH || url.pathname === '/igbo-calendar') {
          await safeCachePut(cache, new Request(APP_SHELL_PATH), fresh);
        }

        return fresh;
      } catch (_) {
        const exact = await cache.match(req);
        if (exact) return exact;

        const shell = await cache.match(APP_SHELL_PATH);
        if (shell) return shell;

        const offline = await cache.match(OFFLINE_PATH);
        if (offline) return offline;

        return new Response('Offline', {
          status: 503,
          headers: { 'Content-Type': 'text/plain; charset=utf-8' }
        });
      }
    })());
    return;
  }

  if (isStaticAsset(url)) {
    event.respondWith((async () => {
      const cache = await caches.open(CACHE_NAME);
      const cached = await cache.match(req);

      const networkFetch = (async () => {
        try {
          const fresh = await fetch(req);
          await safeCachePut(cache, req, fresh);
          return fresh;
        } catch (_) {
          return null;
        }
      })();

      if (cached) {
        networkFetch.catch(() => {});
        return cached;
      }

      const fresh = await networkFetch;
      if (fresh) return fresh;

      return new Response('Offline', {
        status: 503,
        headers: { 'Content-Type': 'text/plain; charset=utf-8' }
      });
    })());
  }
});

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