# Mobile Optimization + PWA for Doctor & Patient Portals Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Optimize doctor and patient portals for mobile view and create installable PWAs for both that show login/register directly when installed.

**Architecture:** Two independent PWAs (Doctor at `/doctor/*`, Patient at `/patient/*` and `/dashboard`) with separate manifests, service workers, and icons. Install banner shows once on mobile browser. When installed, PWAs skip landing pages and show login directly to logged-out users.

**Tech Stack:** Laravel Blade templates, Vite, Tailwind CSS, vanilla JS service workers, PNG icons

---

## Pre-requisites

- Node.js with `canvas` package for icon generation OR Inkscape/ImageMagick for SVG-to-PNG
- Existing `public/sw.js` notification service worker (to be extended for full PWA support)
- Existing Tailwind config with primary blue `#0EA5E9` and green `#10B981`

---

## Phase 1: PWA Icons

### Task 1: Create Doctor Portal Icons

**Files:**
- Create: `public/icons/doctor-icon-192.png`
- Create: `public/icons/doctor-icon-512.png`

**Step 1: Create SVG source**

Create `public/icons/doctor-icon.svg`:
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
  <rect width="512" height="512" rx="64" fill="#0EA5E9"/>
  <g fill="white" transform="translate(100, 120)">
    <!-- Stethoscope icon -->
    <circle cx="156" cy="240" r="48" stroke="white" stroke-width="24" fill="none"/>
    <path d="M60 80 C60 20, 252 20, 252 80 L252 160 C252 220, 60 220, 60 160 Z" stroke="white" stroke-width="24" fill="none" stroke-linejoin="round"/>
    <path d="M60 120 L60 200 C60 260, 252 260, 252 200 L252 120" stroke="white" stroke-width="24" fill="none"/>
    <rect x="126" y="200" width="24" height="72" rx="12"/>
    <circle cx="138" cy="280" r="24"/>
  </g>
</svg>
```

**Step 2: Convert to PNG**

Using ImageMagick (install if needed):
```bash
# Ensure ImageMagick is installed, then:
magick convert -background none public/icons/doctor-icon.svg -resize 192x192 public/icons/doctor-icon-192.png
magick convert -background none public/icons/doctor-icon.svg -resize 512x512 public/icons/doctor-icon-512.png
```

Or using Node.js canvas (if ImageMagick unavailable), create `scripts/generate-icons.js`:
```javascript
const { createCanvas } = require('canvas');
const fs = require('fs');

function generateDoctorIcon(size, outputPath) {
  const canvas = createCanvas(size, size);
  const ctx = canvas.getContext('2d');
  const blue = '#0EA5E9';
  const radius = size * 0.125;

  // Background rounded rect
  ctx.fillStyle = blue;
  ctx.beginPath();
  ctx.roundRect(0, 0, size, size, radius);
  ctx.fill();

  // Stethoscope symbol (scaled)
  ctx.strokeStyle = 'white';
  ctx.lineWidth = size * 0.05;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';

  // Simple stethoscope: listening circle at bottom
  const centerX = size / 2;
  const centerY = size * 0.7;
  const circleR = size * 0.12;
  ctx.beginPath();
  ctx.arc(centerX, centerY, circleR, 0, Math.PI * 2);
  ctx.stroke();

  // Tubing from circle up and around
  ctx.beginPath();
  ctx.moveTo(centerX - circleR, centerY);
  ctx.quadraticCurveTo(centerX - circleR, size * 0.2, size * 0.25, size * 0.2);
  ctx.stroke();

  ctx.beginPath();
  ctx.moveTo(centerX + circleR, centerY);
  ctx.quadraticCurveTo(centerX + circleR, size * 0.2, size * 0.75, size * 0.2);
  ctx.stroke();

  // Y-connector
  ctx.beginPath();
  ctx.moveTo(size * 0.25, size * 0.2);
  ctx.lineTo(size * 0.45, size * 0.35);
  ctx.moveTo(size * 0.75, size * 0.2);
  ctx.lineTo(size * 0.55, size * 0.35);
  ctx.moveTo(size * 0.5, size * 0.35);
  ctx.lineTo(size * 0.5, size * 0.5);
  ctx.stroke();

  // Chest piece
  ctx.beginPath();
  ctx.arc(size * 0.5, size * 0.5, size * 0.08, 0, Math.PI * 2);
  ctx.stroke();

  const buffer = canvas.toBuffer('image/png');
  fs.writeFileSync(outputPath, buffer);
}

generateDoctorIcon(192, 'public/icons/doctor-icon-192.png');
generateDoctorIcon(512, 'public/icons/doctor-icon-512.png');
console.log('Doctor icons generated');
```

**Step 3: Run icon generation**
```bash
node scripts/generate-icons.js
```

**Step 4: Commit**
```bash
git add public/icons/
git commit -m "feat(pwa): add doctor portal PWA icons"
```

---

### Task 2: Create Patient Portal Icons

**Files:**
- Create: `public/icons/patient-icon-192.png`
- Create: `public/icons/patient-icon-512.png`

**Step 1: Add to scripts/generate-icons.js**

Append patient icon generation:
```javascript
function generatePatientIcon(size, outputPath) {
  const canvas = createCanvas(size, size);
  const ctx = canvas.getContext('2d');
  const green = '#10B981';
  const radius = size * 0.125;

  // Background rounded rect
  ctx.fillStyle = green;
  ctx.beginPath();
  ctx.roundRect(0, 0, size, size, radius);
  ctx.fill();

  // Heart icon centered
  ctx.fillStyle = 'white';
  ctx.beginPath();
  const cx = size / 2;
  const cy = size * 0.45;
  const heartSize = size * 0.35;

  ctx.moveTo(cx, cy + heartSize * 0.3);
  ctx.bezierCurveTo(cx - heartSize * 0.05, cy, cx - heartSize * 0.5, cy, cx - heartSize * 0.5, cy + heartSize * 0.2);
  ctx.bezierCurveTo(cx - heartSize * 0.5, cy + heartSize * 0.5, cx, cy + heartSize * 0.8, cx, cy + heartSize * 0.8);
  ctx.bezierCurveTo(cx, cy + heartSize * 0.8, cx + heartSize * 0.5, cy + heartSize * 0.5, cx + heartSize * 0.5, cy + heartSize * 0.2);
  ctx.bezierCurveTo(cx + heartSize * 0.5, cy, cx + heartSize * 0.05, cy, cx, cy + heartSize * 0.3);
  ctx.fill();

  // Plus sign on heart
  ctx.fillStyle = green;
  const plusW = size * 0.12;
  const plusH = size * 0.3;
  const plusX = cx - plusW / 2;
  const plusY = cy + heartSize * 0.1;
  ctx.fillRect(plusX, plusY, plusW, plusH);
  ctx.fillRect(cx - plusH / 2, cy + heartSize * 0.05, plusH, plusW);

  const buffer = canvas.toBuffer('image/png');
  fs.writeFileSync(outputPath, buffer);
}

generatePatientIcon(192, 'public/icons/patient-icon-192.png');
generatePatientIcon(512, 'public/icons/patient-icon-512.png');
console.log('Patient icons generated');
```

**Step 2: Run and commit**
```bash
node scripts/generate-icons.js
git add public/icons/
git commit -m "feat(pwa): add patient portal PWA icons"
```

---

## Phase 2: PWA Manifests

### Task 3: Create Doctor PWA Manifest

**Files:**
- Create: `public/doctor-manifest.webmanifest`

**Step 1: Write manifest file**

```json
{
  "name": "Medicine AI - Doctor Portal",
  "short_name": "Doctor App",
  "description": "Medicine AI Doctor Portal - Manage appointments, patients, and analytics",
  "start_url": "/doctor/dashboard",
  "scope": "/doctor",
  "display": "standalone",
  "orientation": "portrait",
  "background_color": "#0EA5E9",
  "theme_color": "#0EA5E9",
  "lang": "en",
  "icons": [
    {
      "src": "/icons/doctor-icon-192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any maskable"
    },
    {
      "src": "/icons/doctor-icon-512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any maskable"
    }
  ],
  "categories": ["medical", "productivity"],
  "screenshots": [],
  "prefer_related_applications": false
}
```

**Step 2: Commit**
```bash
git add public/doctor-manifest.webmanifest
git commit -m "feat(pwa): add doctor portal manifest"
```

---

### Task 4: Create Patient PWA Manifest

**Files:**
- Create: `public/patient-manifest.webmanifest`

**Step 1: Write manifest file**

```json
{
  "name": "Medicine AI - Patient Portal",
  "short_name": "Patient App",
  "description": "Medicine AI Patient Portal - Manage appointments and health records",
  "start_url": "/dashboard",
  "scope": "/patient",
  "display": "standalone",
  "orientation": "portrait",
  "background_color": "#10B981",
  "theme_color": "#10B981",
  "lang": "en",
  "icons": [
    {
      "src": "/icons/patient-icon-192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any maskable"
    },
    {
      "src": "/icons/patient-icon-512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any maskable"
    }
  ],
  "categories": ["medical", "health"],
  "prefer_related_applications": false
}
```

**Step 2: Commit**
```bash
git add public/patient-manifest.webmanifest
git commit -m "feat(pwa): add patient portal manifest"
```

---

## Phase 3: Service Workers

### Task 5: Create Doctor Service Worker

**Files:**
- Create: `public/doctor-sw.js`

**Step 1: Write service worker**

```javascript
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

// Fetch: network-first for API, cache-first for assets
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET and cross-origin requests
  if (request.method !== 'GET') return;
  if (!url.origin.includes(window.location.origin)) return;

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
            return cached || caches.match('/login');
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
      });
    })
  );
});

// Listen for install prompt
let deferredPrompt;

self.addEventListener('message', (event) => {
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
  }
});
```

**Step 2: Commit**
```bash
git add public/doctor-sw.js
git commit -m "feat(pwa): add doctor portal service worker"
```

---

### Task 6: Create Patient Service Worker

**Files:**
- Create: `public/patient-sw.js`

**Step 1: Write service worker (similar to doctor but scoped to patient assets)**

```javascript
const PATIENT_CACHE = 'medicine-ai-patient-v1';
const PATIENT_ASSETS = [
  '/',
  '/dashboard',
  '/login',
  '/register',
  '/register/patient',
  '/css/app.css',
  '/css/dashboard.css',
  '/css/custom.css',
  '/icons/patient-icon-192.png',
  '/icons/patient-icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(PATIENT_CACHE).then((cache) => cache.addAll(PATIENT_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key.startsWith('medicine-ai-patient') && key !== PATIENT_CACHE)
          .map((key) => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  if (request.method !== 'GET') return;
  if (!url.origin.includes(window.location.origin)) return;

  if (request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const clone = response.clone();
          caches.open(PATIENT_CACHE).then((cache) => cache.put(request, clone));
          return response;
        })
        .catch(() => {
          return caches.match(request).then((cached) => cached || caches.match('/login'));
        })
    );
    return;
  }

  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) return cached;
      return fetch(request).then((response) => {
        const clone = response.clone();
        caches.open(PATIENT_CACHE).then((cache) => cache.put(request, clone));
        return response;
      });
    })
  );
});

self.addEventListener('message', (event) => {
  if (event.data === 'skipWaiting') self.skipWaiting();
});
```

**Step 2: Commit**
```bash
git add public/patient-sw.js
git commit -m "feat(pwa): add patient portal service worker"
```

---

## Phase 4: Blade Template Modifications

### Task 7: Add PWA Meta Tags & Install Banner to Doctor Layout

**Files:**
- Modify: `resources/views/layouts/doctor.blade.php`

**Step 1: Read the current file**
Read `resources/views/layouts/doctor.blade.php` to understand its structure (find `<head>`, `<body>`, closing tags).

**Step 2: Add PWA meta tags in `<head>`**

After existing `<meta>` tags, add:
```html
<!-- PWA Meta Tags -->
<link rel="manifest" href="/doctor-manifest.webmanifest">
<meta name="theme-color" content="#0EA5E9">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Doctor App">
<link rel="apple-touch-icon" href="/icons/doctor-icon-192.png">
```

**Step 3: Add install banner HTML before `</body>`**

```html
<!-- PWA Install Banner -->
<div id="pwa-install-banner" class="pwa-install-banner" style="display:none;">
  <div class="pwa-banner-content">
    <div class="pwa-banner-icon">
      <img src="/icons/doctor-icon-192.png" alt="Doctor App" width="32" height="32">
    </div>
    <div class="pwa-banner-text">
      <strong>Install Doctor App</strong>
      <span>For a faster, app-like experience</span>
    </div>
    <div class="pwa-banner-buttons">
      <button id="pwa-install-btn" class="btn btn-primary btn-sm">Install</button>
      <button id="pwa-dismiss-btn" class="btn btn-secondary btn-sm">Not now</button>
    </div>
  </div>
</div>

<style>
.pwa-install-banner {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #0c1929;
  color: white;
  padding: 12px 16px;
  z-index: 9999;
  box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
}
.pwa-banner-content {
  display: flex;
  align-items: center;
  gap: 12px;
  max-width: 600px;
  margin: 0 auto;
}
.pwa-banner-text {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.pwa-banner-text span {
  font-size: 12px;
  opacity: 0.8;
}
.pwa-banner-buttons {
  display: flex;
  gap: 8px;
}
.pwa-banner-buttons .btn {
  padding: 6px 16px;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  border: none;
}
.pwa-banner-buttons .btn-primary {
  background: #0EA5E9;
  color: white;
}
.pwa-banner-buttons .btn-secondary {
  background: rgba(255,255,255,0.15);
  color: white;
}
@media (max-width: 480px) {
  .pwa-banner-content {
    flex-wrap: wrap;
  }
  .pwa-banner-text {
    flex: 1 1 calc(100% - 44px);
  }
  .pwa-banner-buttons {
    flex: 1;
    justify-content: flex-end;
  }
}
</style>

<script>
(function() {
  let deferredPrompt;
  const banner = document.getElementById('pwa-install-banner');
  const installBtn = document.getElementById('pwa-install-btn');
  const dismissBtn = document.getElementById('pwa-dismiss-btn');

  // Check if already installed or dismissed
  const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
  const isDismissed = localStorage.getItem('doctorPwaDismissed') === 'true';

  if (!isStandalone && !isDismissed && 'serviceWorker' in navigator) {
    // Register service worker
    navigator.serviceWorker.register('/doctor-sw.js')
      .then(() => console.log('Doctor SW registered'))
      .catch((err) => console.log('Doctor SW registration failed:', err));

    // Show banner after delay
    setTimeout(() => {
      if (!isStandalone && !localStorage.getItem('doctorPwaDismissed')) {
        banner.style.display = 'block';
      }
    }, 30000);
  }

  // Detect if opened as installed PWA (no address bar)
  if (isStandalone) {
    // Already installed - service worker handles offline/login caching
    console.log('Doctor PWA running in standalone mode');
  }

  // Install button handler
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    if (!banner.style.display || banner.style.display === 'none') {
      banner.style.display = 'block';
    }
  });

  installBtn.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    deferredPrompt = null;
    banner.style.display = 'none';
    if (outcome === 'accepted') {
      localStorage.setItem('doctorPwaDismissed', 'true');
    }
  });

  dismissBtn.addEventListener('click', () => {
    banner.style.display = 'none';
    localStorage.setItem('doctorPwaDismissed', 'true');
  });
})();
</script>
```

**Step 3: Commit**
```bash
git add resources/views/layouts/doctor.blade.php
git commit -m "feat(pwa): add PWA meta tags and install banner to doctor layout"
```

---

### Task 8: Add PWA Meta Tags & Install Banner to Patient Layout

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

**Step 1: Read current file**
Read `resources/views/layouts/app.blade.php` to find `<head>` and `</body>` locations.

**Step 2: Add PWA meta tags in `<head>`** (after existing meta tags)
```html
<!-- PWA Meta Tags -->
<link rel="manifest" href="/patient-manifest.webmanifest">
<meta name="theme-color" content="#10B981">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Patient App">
<link rel="apple-touch-icon" href="/icons/patient-icon-192.png">
```

**Step 3: Add install banner before `</body>`** (same structure as doctor, green theme, `/patient-sw.js` registration, `patientPwaDismissed` localStorage key)

**Step 4: Commit**
```bash
git add resources/views/layouts/app.blade.php
git commit -m "feat(pwa): add PWA meta tags and install banner to patient layout"
```

---

### Task 9: Add Standalone Detection to Login Pages

**Files:**
- Modify: `resources/views/auth/login.blade.php`

**Step 1: Read current file**

**Step 2: Add script after `<body>` or in `<head>`**

```html
<script>
(function() {
  // If opened as standalone PWA and not logged in, redirect to login
  // This ensures installed PWAs show login directly
  const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
  if (isStandalone) {
    document.documentElement.classList.add('is-installed-pwa');
  }
})();
</script>
```

**Step 3: Also add PWA meta tags to login page head**

```html
<link rel="manifest" href="/doctor-manifest.webmanifest" id="login-manifest">
<meta name="theme-color" content="#0EA5E9">
```

Note: The manifest link should dynamically change based on referrer or URL detection. Better approach: detect if coming from doctor or patient path:

```html
<script>
(function() {
  const path = window.location.pathname;
  const isDoctorPath = path.startsWith('/doctor') || document.referrer.includes('/doctor');
  const manifestLink = document.getElementById('dynamic-manifest');
  if (manifestLink) {
    manifestLink.href = isDoctorPath ? '/doctor-manifest.webmanifest' : '/patient-manifest.webmanifest';
  }
})();
</script>
```

Add to login blade head:
```html
<link rel="manifest" href="/doctor-manifest.webmanifest" id="dynamic-manifest">
<meta name="theme-color" content="#0EA5E9">
```

**Step 4: Commit**
```bash
git add resources/views/auth/login.blade.php
git commit -m "feat(pwa): add standalone detection to login page"
```

---

### Task 10: Add PWA Meta Tags to Register Choice Page

**Files:**
- Modify: `resources/views/auth/register-choice.blade.php`

**Step 1: Read file and add PWA meta tags in `<head>`**

**Step 2: Commit**
```bash
git add resources/views/auth/register-choice.blade.php
git commit -m "feat(pwa): add PWA meta tags to register choice page"
```

---

## Phase 5: Mobile CSS Optimization

### Task 11: Audit & Fix Doctor Dashboard Mobile CSS

**Files:**
- Modify: `public/css/doctor-dashboard.css`

**Step 1: Audit mobile breakpoints**

Read `public/css/doctor-dashboard.css` and identify:
- Line ~614: existing `@media (max-width: 768px)` block
- Line ~757: existing `@media (max-width: 576px)` block
- Any overflow issues (horizontal scroll, text truncation)

**Step 2: Fix common mobile issues**

Add comprehensive mobile styles at end of file. Key fixes to implement:

```css
/* === Mobile Optimization === */
@media (max-width: 768px) {
  /* Fix overflow */
  .doctor-content,
  .dashboard-container {
    overflow-x: hidden;
    width: 100%;
  }

  /* Sidebar fixes */
  .doctor-sidebar {
    transform: translateX(-100%);
    position: fixed;
    z-index: 1000;
    top: 0;
    left: 0;
    height: 100vh;
    width: 280px;
    transition: transform 0.3s ease;
    box-shadow: 2px 0 10px rgba(0,0,0,0.2);
  }
  .doctor-sidebar.show {
    transform: translateX(0);
  }
  .sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999;
  }
  .sidebar-overlay.show {
    display: block;
  }

  /* Stats cards - stack vertically */
  .stats-grid,
  .info-cards-grid,
  .dashboard-stats {
    grid-template-columns: 1fr !important;
    gap: 12px;
  }
  .stat-card {
    min-width: 0;
    width: 100%;
  }

  /* Tables - horizontal scroll */
  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .data-table {
    min-width: 600px;
  }

  /* Dashboard header */
  .dashboard-header {
    padding: 16px;
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .dashboard-header h1 {
    font-size: 1.5rem !important;
  }

  /* Cards padding */
  .card-body {
    padding: 16px;
  }

  /* Buttons */
  .btn {
    min-height: 44px;
    min-width: 44px;
    font-size: 14px;
  }

  /* Forms */
  .form-control,
  .form-select {
    min-height: 44px;
    font-size: 16px; /* Prevents iOS zoom on focus */
  }

  /* Workflow buttons */
  .workflow-buttons {
    flex-direction: column;
  }
}

@media (max-width: 576px) {
  /* Further small screen fixes */
  .appointment-details {
    padding: 0 8px;
  }
  .modal-dialog {
    margin: 8px;
    max-width: calc(100% - 16px);
  }
  .table-card {
    padding: 12px;
  }
  h1, h2, h3 {
    font-size: 1.25rem !important;
  }
  .stats-number {
    font-size: 1.75rem !important;
  }
  .navbar-brand {
    font-size: 1rem;
  }
}
```

**Step 3: Verify in browser** (mobile emulator in Chrome DevTools)

**Step 4: Commit**
```bash
git add public/css/doctor-dashboard.css
git commit -m "fix(mobile): comprehensive mobile responsiveness for doctor dashboard"
```

---

### Task 12: Audit & Fix Patient Dashboard Mobile CSS

**Files:**
- Modify: `public/css/dashboard.css`

**Step 1: Read file and identify mobile breakpoints**

**Step 2: Add comprehensive mobile fixes** (similar pattern to Task 11):
- Grid/table overflow
- Navigation hamburger
- Card stacking
- Touch target sizing
- iOS font zoom prevention

**Step 3: Also check and fix:**
- `public/css/custom.css` for responsive modal and form issues
- `public/css/responsive-modals.css` already has some fixes — verify they're comprehensive

**Step 4: Commit**
```bash
git add public/css/dashboard.css public/css/custom.css
git commit -m "fix(mobile): comprehensive mobile responsiveness for patient dashboard"
```

---

### Task 13: Fix Mobile Navigation for Both Portals

**Files:**
- Modify: `resources/views/layouts/doctor.blade.php` (add hamburger toggle)
- Modify: `resources/views/layouts/navigation.blade.php` (check existing Alpine.js mobile toggle)

**Step 1: Read navigation.blade.php**

**Step 2: Verify hamburger menu works on mobile** (existing Alpine.js code: `x-data="{ open: false }"`)

**Step 3: If broken, fix the mobile toggle JavaScript/Alpine**

**Step 4: Commit**

---

## Phase 6: Offline Login Page Support

### Task 14: Create Offline Fallback Page

**Files:**
- Create: `resources/views/offline.blade.php`

**Step 1: Write offline page**

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>You're Offline - Medicine AI</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f8fafc;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 20px;
      text-align: center;
    }
    .offline-container {
      max-width: 400px;
    }
    .offline-icon {
      font-size: 64px;
      margin-bottom: 20px;
    }
    h1 {
      color: #1e293b;
      font-size: 1.5rem;
      margin-bottom: 12px;
    }
    p {
      color: #64748b;
      line-height: 1.6;
    }
    .offline-card {
      background: white;
      border-radius: 16px;
      padding: 40px 24px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    }
    .status-dot {
      display: inline-block;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #ef4444;
      margin-right: 8px;
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.4; }
    }
  </style>
</head>
<body>
  <div class="offline-container">
    <div class="offline-card">
      <div class="offline-icon">📡</div>
      <h1><span class="status-dot"></span>You're Offline</h1>
      <p>Please check your internet connection and try again. Your login credentials will work once you're back online.</p>
    </div>
  </div>
</body>
</html>
```

**Step 2: Update service workers to serve this page when offline and HTML not cached**

In both `doctor-sw.js` and `patient-sw.js`, update the HTML fetch handler:
```javascript
.catch(() => {
  return caches.match(request).then((cached) => cached || caches.match('/offline'));
})
```

**Step 3: Also cache the offline page in the precache list:**
Add `'/'` entry serves as fallback but specific `/offline` is better.

**Step 4: Commit**
```bash
git add resources/views/offline.blade.php public/doctor-sw.js public/patient-sw.js
git commit -m "feat(pwa): add offline fallback page"
```

---

## Verification

### Manual Testing Steps

**Test 1: Mobile CSS on Doctor Portal**
1. Open Chrome DevTools → Toggle device toolbar (Ctrl+Shift+M)
2. Set device to "iPhone 14" or "Pixel 7"
3. Navigate to `/doctor/dashboard`
4. Verify: no horizontal overflow, sidebar toggles with hamburger, cards stack, buttons are ≥44px tap target
5. Fix any remaining overflow issues found

**Test 2: Mobile CSS on Patient Portal**
1. Same as above, navigate to `/dashboard`
2. Verify same checklist as Test 1

**Test 3: PWA Install Banner (Doctor)**
1. Open doctor portal on mobile Chrome (use real device or emulator)
2. Wait 30 seconds or scroll
3. Verify: bottom install banner appears
4. Click "Not now" → banner disappears
5. Refresh page → banner should NOT reappear
6. Clear site data → banner reappears

**Test 4: PWA Install Prompt**
1. On mobile Chrome, visit doctor portal
2. Tap "Install" button on banner
3. Verify: native "Add to Home Screen" dialog appears
4. Cancel or accept

**Test 5: Installed PWA Flow**
1. Install Doctor App from Chrome mobile
2. Open from home screen
3. Verify: login page shows directly (not landing page or redirect loops)
4. Login works and redirects to dashboard

**Test 6: Offline Login**
1. Put site offline in DevTools → Network tab → "Offline"
2. Visit `/login`
3. Verify: page loads, offline indicator visible, form visible
4. Try to submit → shows offline queue message

---

## File Summary

| Action | File |
|--------|------|
| Create | `public/icons/doctor-icon.svg` |
| Create | `public/icons/doctor-icon-192.png` |
| Create | `public/icons/doctor-icon-512.png` |
| Create | `public/icons/patient-icon-192.png` |
| Create | `public/icons/patient-icon-512.png` |
| Create | `public/doctor-manifest.webmanifest` |
| Create | `public/patient-manifest.webmanifest` |
| Create | `public/doctor-sw.js` |
| Create | `public/patient-sw.js` |
| Create | `scripts/generate-icons.js` |
| Create | `resources/views/offline.blade.php` |
| Modify | `resources/views/layouts/doctor.blade.php` |
| Modify | `resources/views/layouts/app.blade.php` |
| Modify | `resources/views/auth/login.blade.php` |
| Modify | `resources/views/auth/register-choice.blade.php` |
| Modify | `public/css/doctor-dashboard.css` |
| Modify | `public/css/dashboard.css` |
| Modify | `public/css/custom.css` |
