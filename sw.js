/**
 * Service Worker — Absensi MAN 2 Lombok Timur PWA
 * Provides offline caching, install capability, and faster repeat loads.
 */

const CACHE_NAME = 'absensi-pwa-v2';

// Core shell assets to pre-cache on install
const PRECACHE_URLS = [
  '/',
  '/index.php',
  '/login.php',
  '/assets/css/style.css',
  '/assets/pwa/pwa-icon-192x192.png',
  '/assets/pwa/pwa-icon-512x512.png',
  '/assets/pwa/pwa-icon-180x180.png',
  '/manifest.json',
  'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js',
  'https://unpkg.com/@zxing/library@0.19.1/umd/index.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// ─── Install ─────────────────────────────────────────────
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Pre-caching app shell');
      return cache.addAll(PRECACHE_URLS);
    })
  );
  // Activate immediately without waiting for old SW to finish
  self.skipWaiting();
});

// ─── Activate ────────────────────────────────────────────
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => {
            console.log('[SW] Deleting old cache:', name);
            return caches.delete(name);
          })
      );
    })
  );
  // Take control of all clients immediately
  self.clients.claim();
});

// ─── Fetch — Network-first with cache fallback ──────────
self.addEventListener('fetch', (event) => {
  const { request } = event;

  // Skip non-GET and API/auth/ajax requests
  if (request.method !== 'GET') return;
  if (request.url.includes('/ajax/')) return;
  if (request.url.includes('portal_')) return; // let portals hit network
  if (request.url.includes('login') && request.method === 'POST') return;

  // For navigation requests (HTML pages) — network-first
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Cache the latest version
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
          return response;
        })
        .catch(() => {
          // Offline — serve from cache
          return caches.match(request).then((cached) => {
            return cached || caches.match('/index.php');
          });
        })
    );
    return;
  }

  // For static assets — cache-first with network fallback
  if (
    request.url.match(/\.(js|css|png|jpg|jpeg|webp|svg|woff2?|ttf|eot)$/) ||
    request.url.includes('fonts.googleapis.com') ||
    request.url.includes('fonts.gstatic.com') ||
    request.url.includes('cdnjs.cloudflare.com')
  ) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) return cached;
        return fetch(request).then((response) => {
          // Only cache successful responses
          if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
          }
          return response;
        });
      })
    );
    return;
  }

  // Default — network-first
  event.respondWith(
    fetch(request)
      .then((response) => {
        if (response.ok) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
        }
        return response;
      })
      .catch(() => caches.match(request))
  );
});
