/* Roshbar POS service worker.
 * - /pos page: network first, falls back to the last copy when offline.
 * - App files (css, js, Bootstrap, icons, product photos): cache first.
 * - Everything else (orders, admin, website) is never cached here.
 * Orders taken offline are queued by pos.js, not by this worker. */
const VERSION = 'roshbar-pos-v1';
const STATIC = /^\/(vendor|css|js|storage\/products)\//;

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(VERSION).then((c) => c.addAll([
    '/vendor/bootstrap/bootstrap.min.css',
    '/vendor/bootstrap/bootstrap.bundle.min.js',
    '/vendor/bootstrap-icons/bootstrap-icons.min.css',
  ]).catch(() => {})));
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys()
    .then((keys) => Promise.all(keys.filter((k) => k.startsWith('roshbar-pos-') && k !== VERSION).map((k) => caches.delete(k))))
    .then(() => self.clients.claim()));
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  if (url.pathname === '/pos' && req.mode === 'navigate') {
    e.respondWith(fetch(req).then((res) => {
      // Only keep a real POS page (not a redirect to the login screen).
      if (res.ok && !res.redirected) {
        const copy = res.clone();
        caches.open(VERSION).then((c) => c.put('/pos', copy));
      }
      return res;
    }).catch(() => caches.match('/pos').then((hit) => hit || new Response(
      '<h1 style="font-family:sans-serif">Offline</h1><p style="font-family:sans-serif">Open the POS once while online, then it works offline too.</p>',
      { headers: { 'Content-Type': 'text/html; charset=utf-8' } }))));
    return;
  }

  if (STATIC.test(url.pathname)) {
    e.respondWith(caches.match(req).then((hit) => hit || fetch(req).then((res) => {
      if (res.ok) {
        const copy = res.clone();
        caches.open(VERSION).then((c) => c.put(req, copy));
      }
      return res;
    })));
  }
});
