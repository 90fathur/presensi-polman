self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function(event) {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { body: event.data ? event.data.text() : '' };
    }

    const title = data.title || 'Presensi Polman';
    const options = {
        body: data.body || 'Waktunya absen!',
        icon: '/logo-192.png', // Pastikan file ini ada di folder public
        vibrate: [200, 100, 200],
        tag: 'presensi-notif',
        renotify: true, // Agar kalau ada notif baru, dia bunyi lagi
        data: {
            url: data.url || '/dashboard'
        }
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    // Buka aplikasi saat diklik
    event.waitUntil(
        clients.matchAll({type: 'window'}).then(clientsArr => {
            // Jika sudah terbuka, fokuskan
            const hadWindowToFocus = clientsArr.some(windowClient => windowClient.url === event.notification.data.url ? (windowClient.focus(), true) : false);
            // Jika belum, buka baru
            if (!hadWindowToFocus) clients.openWindow(event.notification.data.url);
        })
    );
});