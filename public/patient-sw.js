// CACHE VERSION: Update this when deploying new service worker assets
const PATIENT_CACHE = 'medicine-ai-patient-v1';
const PATIENT_ASSETS = [
  '/',
  '/dashboard',
  '/login',
  '/register',
  '/register/patient',
  '/offline',
  '/css/app.css',
  '/css/dashboard.css',
  '/css/custom.css',
  '/icons/patient-icon-192.png',
  '/icons/patient-icon-512.png',
  '/patient-manifest.webmanifest',
];

// Install: precache app shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(PATIENT_CACHE).then((cache) => {
      return cache.addAll(PATIENT_ASSETS);
    }).catch((err) => {
      console.error('Patient SW install failed:', err);
    })
  );
  self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key.startsWith('medicine-ai-patient') && key !== PATIENT_CACHE)
          .map((key) => caches.delete(key))
      );
    }).catch((err) => {
      console.error('Patient SW activation failed:', err);
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
  if (url.origin !== self.location.origin) return;

  // Network-first for HTML pages (login, dashboard)
  if (request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const clone = response.clone();
          caches.open(PATIENT_CACHE).then((cache) => {
            cache.put(request, clone).catch((err) => {
              console.error('Patient SW cache put failed:', err);
            });
          });
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
        caches.open(PATIENT_CACHE).then((cache) => {
          cache.put(request, clone).catch((err) => {
            console.error('Patient SW cache put failed:', err);
          });
        });
        return response;
      });
    }).catch(() => {
      return new Response('Offline', { status: 503 });
    })
  );
});

// Listen for messages
self.addEventListener('message', (event) => {
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
  }
});
