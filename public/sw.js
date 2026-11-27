/* SRM Fintech Solutions — service worker (makes the site installable as an app).
 * Safety first for a money app: pages and data are ALWAYS fetched live from the server and never cached.
 * Only static files (CSS, JS, images, fonts) are cached for speed, plus a small offline screen. */
const VERSION = 'srm-v1';
const STATIC = 'srm-static-' + VERSION;
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC).then((c) => c.addAll([OFFLINE_URL, '/images/app/icon-192.png'])).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k.startsWith('srm-static-') && k !== STATIC).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

const isStatic = (url) => /^\/(css|js|images)\//.test(url.pathname) || url.hostname === 'fonts.gstatic.com' || url.hostname === 'fonts.googleapis.com';

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return; // forms, payments, uploads: straight to the server
  const url = new URL(req.url);

  // Pages: always live. If the phone is offline, show the offline screen instead of a browser error.
  if (req.mode === 'navigate') {
    event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  // Static files: serve from cache, refresh in the background.
  if (isStatic(url) && (url.origin === self.location.origin || url.hostname.startsWith('fonts.'))) {
    event.respondWith(
      caches.open(STATIC).then((cache) => cache.match(req).then((hit) => {
        const live = fetch(req).then((res) => { if (res.ok) cache.put(req, res.clone()); return res; }).catch(() => hit);
        return hit || live;
      }))
    );
  }
  // Everything else (private files, order status, chat, admin): network only, never cached.
});
