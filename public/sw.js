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

self.addEventListener('fetch', function (event) {
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
