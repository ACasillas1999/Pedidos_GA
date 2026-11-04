const CACHE_NAME = 'pedidos-app-v9';
const urlsToCache = [
  './',
  './index.html',
  './main.html',
  './css/output.css',
  './js/app.js',
  './js/auth.js',
  './js/main.js',
  './js/lucide.js',
  './js/sweetalert2.js',
  './manifest.json',
  './img/icon.svg'
];

self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('activate', event => {
  const cacheAllowlist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheAllowlist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      return self.clients.claim();
    })
  );
});

// Network-first strategy for app files and bypass for API calls
self.addEventListener('fetch', event => {
  // Las llamadas a la API en App/ nunca se guardan en caché
  if (event.request.url.includes('/App/') || event.request.method !== 'GET') {
    event.respondWith(fetch(event.request));
    return;
  }

  // Network First para archivos de la app con fallback a caché
  event.respondWith(
    fetch(event.request)
      .then(response => {
        if (response && response.status === 200 && response.type === 'basic') {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseToCache);
          });
        }
        return response;
      })
      .catch(() => {
        return caches.match(event.request);
      })
  );
});
