// ToolLab Invoice Service Worker
const CACHE_NAME = 'toollab-invoice-v1';
const STATIC_ASSETS = [
  '/',
  '/favicon.png',
  '/logo.png',
  '/offline.html',
  '/assets/css/app.css',
  '/assets/js/app.js'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  // Only handle GET requests for navigation or static assets
  if (event.request.method !== 'GET') {
    return;
  }

  // Network-first for dynamic application pages, cache-first for static assets
  const url = new URL(event.request.url);
  const isStatic = url.pathname.startsWith('/assets/') || url.pathname === '/favicon.png' || url.pathname === '/logo.png';

  if (isStatic) {
    event.respondWith(
      caches.match(event.request).then((cachedResponse) => {
        return cachedResponse || fetch(event.request);
      })
    );
  } else {
    // Network-first for HTML pages
    event.respondWith(
      fetch(event.request).catch(() => {
        return caches.match('/offline.html');
      })
    );
  }
});
