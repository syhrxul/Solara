self.addEventListener('push', function (event) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    let data = {};
    if (event.data) {
        data = event.data.json();
    }

    const title = data.title || 'Informasi Solara';
    const options = {
        body: data.body || 'Ada pemberitahuan baru.',
        icon: data.icon || '/favicon.ico',
        badge: data.badge || '/favicon.ico',
        data: data.data || {}
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            if (event.notification.data && event.notification.data.url) {
                let client = clientList.find(c => c.url === event.notification.data.url);
                if (client) {
                    client.focus();
                } else {
                    clients.openWindow(event.notification.data.url);
                }
            }
        })
    );
});

const CACHE_NAME = 'solara-static-v1';
const STATIC_ASSETS = [
    '/manifest.json',
    '/apple-touch-icon.png',
    '/favicon.ico'
];

// Network-first strategy for navigation, Stale-while-revalidate for static assets
self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') return;
    if (event.request.url.includes('/livewire')) return;

    const url = new URL(event.request.url);

    // 1. Caching JS, CSS, Images, Fonts (Stale-While-Revalidate)
    if (
        url.pathname.match(/\.(css|js|png|jpg|jpeg|svg|woff2|woff|ttf)$/) ||
        url.hostname.includes('fonts.googleapis.com') ||
        url.hostname.includes('fonts.gstatic.com')
    ) {
        event.respondWith(
            caches.match(event.request).then(function (cachedResponse) {
                const fetchPromise = fetch(event.request).then(function (networkResponse) {
                    caches.open(CACHE_NAME).then(function (cache) {
                        cache.put(event.request, networkResponse.clone());
                    });
                    return networkResponse;
                }).catch(function () {
                    // Fail silently for static assets offline
                });

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // 2. Network-first for HTML pages (Fallback Offline Screen)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(function () {
                return new Response('<html><head><title>📱 Solara Mode Offline</title><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="background:#0f172a;color:#fff;font-family:sans-serif;text-align:center;padding-top:20vh;"><h2>🌙 Anda Sedang Offline</h2><p>Ponsel/laptop tidak terhubung ke jaringan.</p><p>Periksa konektivitas untuk membuka kembali panel kerja produktivitas Anda.</p></body></html>', {
                    status: 200,
                    headers: { 'Content-Type': 'text/html' }
                });
            })
        );
    }
});

// Force update: when a new SW is installed, activate immediately
self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        Promise.all([
            // Take control of all clients immediately
            self.clients.claim(),
            // Clear any old caches
            caches.keys().then(function (cacheNames) {
                return Promise.all(
                    cacheNames.map(function (cacheName) {
                        return caches.delete(cacheName);
                    })
                );
            })
        ])
    );
});
