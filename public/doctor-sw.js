const DOCTOR_CACHE = 'medicine-ai-doctor-v1';
const DOCTOR_ASSETS = [
  '/',
  '/doctor/dashboard',
  '/login',
  '/register-doctor',
  '/css/doctor-dashboard.css',
  '/css/dashboard.css',
  '/css/app.css',
  '/icons/doctor-icon-192.png',
  '/icons/doctor-icon-512.png',
  '/doctor-manifest.webmanifest',
];

// Install: precache app shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(DOCTOR_CACHE).then((cache) => {
      return cache.addAll(DOCTOR_ASSETS);
    })
  );
  self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key.startsWith('medicine-ai-doctor') && key !== DOCTOR_CACHE)
          .map((key) => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

// Fetch: network-first for HTML, cache-first for assets
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET and cross-origin requests
  if (request.method !== 'GET') return;
  if (!url.origin.includes(self.location.origin)) return;

  // Network-first for HTML pages (login, dashboard)
  if (request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const clone = response.clone();
          caches.open(DOCTOR_CACHE).then((cache) => cache.put(request, clone));
          return response;
        })
        .catch(() => {
          return caches.match(request).then((cached) => {
            return cached || caches.match('/offline') || new Response('Offline', { status: 503 });
          });
        })
    );
    return;
  }

  // Cache-first for assets
  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) return cached;
      return fetch(request).then((response) => {
        const clone = response.clone();
        caches.open(DOCTOR_CACHE).then((cache) => cache.put(request, clone));
        return response;
      }).catch(() => cached);
    })
  );
});

// Listen for messages
self.addEventListener('message', (event) => {
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
  }
});
