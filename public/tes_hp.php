<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnosa PWA</title>
    <style>
        body { background: #000; color: #0f0; font-family: monospace; padding: 20px; }
        .log { border-bottom: 1px solid #333; padding: 5px 0; }
        .error { color: #f00; font-weight: bold; }
        .success { color: cyan; font-weight: bold; }
        button { padding: 15px; width: 100%; font-size: 18px; margin-bottom: 20px; cursor: pointer; }
    </style>
</head>
<body>
    <h2>LOG DIAGNOSA HP</h2>
    <div id="status">Menunggu perintah...</div>
    <br>
    <button onclick="cekStatus()">1. CEK STATUS</button>
    <button onclick="daftarNotif()">2. DAFTAR NOTIFIKASI</button>
    <div id="consoleArea"></div>

    <script>
        function log(msg, type = 'normal') {
            const area = document.getElementById('consoleArea');
            const color = type === 'error' ? 'red' : (type === 'success' ? 'cyan' : '#0f0');
            area.innerHTML += `<div class='log' style='color:${color}'> > ${msg}</div>`;
        }

        // KUNCI PUBLIK BAPAK (Pastikan ini benar)
        const publicKey = 'BMtiwafw1nabbC34eMvHKGuVtgOWzik92EJ_VKLXTxlxJmawMtHkdFYhAtuVupLXGYnOp3XoborOdURMZeEWIPA';

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        async function cekStatus() {
            log("Memeriksa komponen...", 'normal');
            
            if (!('serviceWorker' in navigator)) {
                log("GAGAL: Service Worker tidak didukung di WebView ini!", 'error');
                return;
            }
            log("OK: Service Worker didukung.", 'success');

            if (!('PushManager' in window)) {
                log("GAGAL: PushManager tidak ada. Mungkin karena HTTP (bukan HTTPS) atau settingan WebView salah.", 'error');
                return;
            }
            log("OK: PushManager ada.", 'success');

            const reg = await navigator.serviceWorker.getRegistration();
            if (reg) {
                log("Info SW: Aktif di " + reg.scope, 'normal');
            } else {
                log("WARNING: Service Worker belum terinstall.", 'error');
            }
        }

        async function daftarNotif() {
            try {
                log("Meminta izin notifikasi...", 'normal');
                // INI YANG SERING GAGAL DI ANDROID
                const permission = await Notification.requestPermission();
                log("Status Izin: " + permission, permission === 'granted' ? 'success' : 'error');

                if (permission !== 'granted') {
                    log("SOLUSI: Buka Pengaturan HP > Aplikasi > Presensi > Notifikasi > ON.", 'error');
                    return;
                }

                log("Mendaftarkan Service Worker...", 'normal');
                // Ganti 'sw.js' sesuai lokasi asli file Bapak
                const reg = await navigator.serviceWorker.register('/sw.js');
                await navigator.serviceWorker.ready;
                log("SW Registered!", 'success');

                log("Subscribing (Mengambil Kunci HP)...", 'normal');
                const sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicKey)
                });
                
                log("BERHASIL DAPAT KUNCI!", 'success');
                log("Endpoint: " + sub.endpoint.substring(0, 30) + "...", 'normal');
                
                // Cek apakah Google atau Mozilla
                if(sub.endpoint.includes('google')) {
                    log("VALID: Kunci dari Google FCM.", 'success');
                } else {
                    log("ANEH: Kunci bukan dari Google (" + sub.endpoint + ")", 'error');
                }

                // Kirim ke Server
                log("Mengirim ke Database...", 'normal');
                const rawResponse = await fetch('/notifikasi/subscribe', {
                    method: 'POST',
                    body: JSON.stringify(sub),
                    headers: { 'Content-Type': 'application/json' }
                });
                const content = await rawResponse.json();
                log("Respon Server: " + JSON.stringify(content), 'success');

            } catch (err) {
                log("CRITICAL ERROR: " + err.message, 'error');
                log("Jejak: " + err.stack, 'normal');
            }
        }
    </script>
</body>
</html>