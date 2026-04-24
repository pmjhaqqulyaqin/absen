const CACHE_NAME = 'absensi-v1';
const urlsToCache = [
  './',
  './index.php',
  './login.php',
  './assets/css/style.css',
  'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js',
  'https://unpkg.com/@zxing/library@0.19.1/umd/index.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  // Hanya intercept GET request, abaikan POST/AJAX
  if (event.request.method !== 'GET') return;
  
  // Jangan cache request API / ajax
  if (event.request.url.includes('ajax/')) return;

  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Cache hit - return response
        if (response) {
          return response;
        }
        return fetch(event.request).catch(() => {
            // Jika gagal fetch (offline), dan request html, kembalikan ke index
            if (event.request.headers.get('accept').includes('text/html')) {
                return caches.match('./index.php');
            }
        });
      })
  );
});
