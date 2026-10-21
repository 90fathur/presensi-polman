<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Presensi AI Polman</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">

    <link rel="apple-touch-icon" href="<?= base_url('assets/img/logo_180.png') ?>">

    <style>
        :root {
            --admin-dark: #0f172a;
            --admin-accent: #3b82f6;
            --gold-soft: #fbbf24;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--admin-dark);
            /* Pola Background Geometris Halus */
            background-image: radial-gradient(circle at 2px 2px, rgba(255, 255, 255, 0.05) 1px, transparent 0);
            background-size: 40px 40px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .admin-login-wrapper {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        /* Garis Aksen Emas di Atas */
        .login-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--admin-accent), var(--gold-soft));
        }

        .header-section {
            padding: 50px 40px 30px;
            text-align: center;
        }

        .header-section img {
            width: 80px;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }

        .admin-badge {
            background: rgba(59, 130, 246, 0.1);
            color: var(--admin-accent);
            font-size: 0.65rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .main-title {
            font-weight: 800;
            color: var(--admin-dark);
            font-size: 1.6rem;
            letter-spacing: -1px;
        }

        .form-section {
            padding: 0 45px 50px;
        }

        .input-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: block;
        }

        .custom-input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .custom-input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            transition: color 0.3s;
        }

        .form-control {
            height: 55px;
            padding-left: 50px;
            border-radius: 16px;
            background: #f8fafc;
            border: 2px solid #f1f5f9;
            font-weight: 500;
            color: var(--admin-dark);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: #fff;
            border-color: var(--admin-accent);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .form-control:focus+i {
            color: var(--admin-accent);
        }

        .btn-admin-login {
            background: var(--admin-dark);
            color: white;
            border: none;
            height: 55px;
            border-radius: 16px;
            font-weight: 700;
            width: 100%;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-admin-login:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.3);
        }

        .footer-info {
            text-align: center;
            margin-top: 30px;
            color: #94a3b8;
            font-size: 0.75rem;
        }

        /* Styling Alert Error */
        .alert-error {
            background: #fff1f2;
            color: #be123c;
            border: none;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="admin-login-wrapper animate__animated animate__fadeInUp">
        <div class="login-card">
            <div class="header-section">
                <img src="https://upload.wikimedia.org/wikipedia/commons/9/9b/Lambang_Kabupaten_Polewali_Mandar.png" alt="Logo Polman">
                <br>
                <span class="admin-badge">Administrator Portal</span>
                <h1 class="main-title">Presensi AI</h1>
                <p class="text-muted small">Kelola data kehadiran dengan kendali penuh</p>
            </div>

            <div class="form-section">
                <?php if (session()->getFlashdata('msg')): ?>
                    <div class="alert alert-error animate__animated animate__shakeX mb-4">
                        <i class="fas fa-shield-alt me-2"></i> <?= session()->getFlashdata('msg') ?>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('auth/login') ?>" method="post">
                    <?= csrf_field() ?>

                    <span class="input-label">Admin Identity</span>
                    <div class="custom-input-group">
                        <input type="text" name="username" class="form-control" placeholder="Input username" required autofocus>
                        <i class="fas fa-user-shield"></i>
                    </div>

                    <span class="input-label">Secret Credential</span>
                    <div class="custom-input-group">
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        <i class="fas fa-key"></i>
                    </div>

                    <button type="submit" class="btn btn-admin-login">
                        Otentikasi Sistem <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="footer-info">
                    <strong>Dinas Kominfo Polewali Mandar</strong><br>
                    Secure Admin Panel Infrastructure &copy; 2026
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="<?= base_url('/') ?>" class="text-decoration-none text-muted small">
                <i class="fas fa-mobile-alt me-1"></i> Buka Versi Mobile Pegawai
            </a>
        </div>
    </div>

</body>

</html>