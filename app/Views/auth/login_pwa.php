<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Presensi Polman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Presensi Polman">

    <link rel="apple-touch-icon" href="<?= base_url('logo-192.png') ?>">
    <style>
        :root {
            --bg-dark: #0f172a;
            --card-dark: #1e293b;
            --accent-green: #22c55e;
            --text-main: #f8fafc;
        }

        body {
            background-color: var(--bg-dark);
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            color: var(--text-main);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            width: 100%;
            max-width: 350px;
            background: var(--card-dark);
            border-radius: 24px;
            padding: 45px 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.6s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .logo-img {
            width: 65px;
            height: auto;
            margin-bottom: 20px;
            filter: drop-shadow(0 0 10px rgba(34, 197, 94, 0.2));
        }

        .header-title {
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .header-subtitle {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 35px;
        }

        .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
        }

        .form-control {
            background-color: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 14px 16px;
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            background-color: rgba(15, 23, 42, 0.8);
            border-color: var(--accent-green);
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
            color: white;
            outline: none;
        }

        .btn-login {
            background-color: var(--accent-green);
            color: #052e16;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
            width: 100%;
            margin-top: 20px;
            transition: all 0.2s;
        }

        .btn-login:hover {
            background-color: #4ade80;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(34, 197, 94, 0.4);
        }

        .alert {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            font-size: 0.8rem;
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 20px;
        }

        .footer-text {
            position: absolute;
            bottom: 30px;
            font-size: 0.75rem;
            color: #475569;
            text-align: center;
            width: 100%;
        }
    </style>
</head>

<body>

    <div class="login-card text-center">
        <img src="https://polmankab.go.id//assets/uploads/summernote/SUMMER_0_1734504076.png" class="logo-img" alt="Logo">

        <div class="header-title">Presensi Polman</div>
        <div class="header-subtitle">Silakan autentikasi identitas Anda</div>

        <?php if (session()->getFlashdata('msg')): ?>
            <div class="alert">
                <i class="fas fa-circle-exclamation me-2"></i><?= session()->getFlashdata('msg') ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" action="<?= base_url('authpegawai/login') ?>" method="post" class="text-start">
            <div class="mb-3">
                <label class="form-label">NIP / Username</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan NIP" required autocomplete="off">
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="button" onclick="handleLoginDanNotif()" class="btn btn-login">
                MASUK SEKARANG
            </button>
        </form>

        <div class="mt-4">
            <a href="#" class="text-decoration-none small" style="color: #64748b;">Lupa Password?</a>
        </div>
    </div>

    <div class="footer-text">
        Dinas Kominfo SP <br>
        <strong>Kabupaten Polewali Mandar</strong>
    </div>

    <script>
        // 1. Registrasi Service Worker di Awal
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= base_url('sw.js') ?>')
                    .then(reg => console.log('SW Registered'))
                    .catch(err => console.log('SW Error:', err));
            });
        }

        // 2. Kunci Publik VAPID (Jangan Diubah)
        const publicKey = 'BMtiwafw1nabbC34eMvHKGuVtgOWzik92EJ_VKLXTxlxJmawMtHkdFYhAtuVupLXGYnOp3XoborOdURMZeEWIPA';

        // 3. Fungsi Helper Konversi Key
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

        // 4. FUNGSI UTAMA: Login + Notifikasi (Versi Anti-Putus)
        async function handleLoginDanNotif() {
            const form = document.getElementById('loginForm');
            const btn = document.querySelector('.btn-login');

            // Validasi Input Dulu
            const userField = document.getElementById('username');
            const passField = document.getElementById('password');

            if (!userField.value || !passField.value) {
                form.reportValidity();
                return;
            }

            // Ubah Tampilan Tombol (Feedback User)
            btn.innerText = "MENGHUBUNGKAN...";
            btn.disabled = true;

            // Logika Pendaftaran Notifikasi
            if ('serviceWorker' in navigator && 'PushManager' in window) {
                try {
                    // Minta Izin
                    const permission = await Notification.requestPermission();

                    if (permission === 'granted') {
                        const reg = await navigator.serviceWorker.ready;

                        // Subscribe
                        const sub = await reg.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(publicKey)
                        });

                        // KIRIM KE SERVER (AWAIT = Tunggu sampai selesai!)
                        // Ini yang menjamin data tersimpan sebelum halaman pindah
                        const respon = await fetch('<?= base_url('notifikasi/subscribe') ?>', {
                            method: 'POST',
                            body: JSON.stringify(sub),
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        });

                        // Opsional: Cek respon server di console
                        const hasil = await respon.json();
                        console.log("Status DB:", hasil);
                    }
                } catch (err) {
                    console.error("Gagal Notifikasi (Tapi Login Tetap Lanjut):", err);
                }
            }

            // SETELAH SEMUA SELESAI, BARU SUBMIT FORM
            btn.innerText = "MEMVERIFIKASI...";
            form.submit();
        }

        // 5. Anti-Spoofing (Detect Fake iPhone)
        window.onload = function() {
            const userAgent = navigator.userAgent;
            const isRealTouch = navigator.maxTouchPoints >= 5;
            const isRetina = window.devicePixelRatio >= 2;

            if (userAgent.includes('iPhone') || userAgent.includes('iPad')) {
                if (!isRealTouch || !isRetina) {
                    document.body.innerHTML = `<div style="padding:50px; text-align:center; color:white;">
                        <i class="fas fa-user-secret fa-5x" style="color:#ef4444"></i>
                        <h2 class="mt-4">Ooppss! Deteksi Kecurangan</h2>
                        <p>Sistem mendeteksi Anda menggunakan perangkat manipulasi.</p>
                        <a href="<?= base_url('authpegawai') ?>" class="btn btn-primary mt-3">Kembali</a>
                    </div>`;
                }
            }
        };
    </script>
    <script>
        // Script ini berjalan otomatis saat pegawai sudah masuk ke Dashboard
        // Di sini koneksi internet lebih stabil dan tidak terburu-buru
        window.addEventListener('load', function() {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.ready.then(reg => {
                    reg.pushManager.getSubscription().then(sub => {
                        if (sub) {
                            // Kirim ulang kunci ke server
                            fetch('<?= base_url('notifikasi/subscribe') ?>', {
                                    method: 'POST',
                                    body: JSON.stringify(sub),
                                    headers: {
                                        'Content-Type': 'application/json'
                                    }
                                })
                                .then(res => res.json())
                                .then(data => console.log("Sync Sukses:", data))
                                .catch(err => console.error("Sync Gagal:", err));
                        }
                    });
                });
            }
        });
    </script>
</body>

</html>