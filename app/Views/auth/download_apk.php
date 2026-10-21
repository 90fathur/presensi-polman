<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Aplikasi Presensi Android</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f0f2f5;
            background-image: linear-gradient(180deg, #e8f5e9 0%, #ffffff 100%);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .apk-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 35px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            max-width: 480px;
            width: 90%;
            padding: 40px;
        }

        .android-icon {
            color: #3DDC84;
            /* Warna Resmi Android */
            font-size: 90px;
            /* Sedikit diperbesar agar lebih menonjol */
            filter: drop-shadow(0 10px 15px rgba(61, 220, 132, 0.3));
        }

        .btn-download {
            background: linear-gradient(135deg, #3DDC84 0%, #2ecc71 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 18px 30px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 15px 30px rgba(61, 220, 132, 0.4);
        }

        .btn-download:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(61, 220, 132, 0.5);
            color: white;
        }

        .step-box {
            background: #f8fafc;
            border-radius: 20px;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }

        .step-num {
            background: #3DDC84;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-right: 10px;
        }
    </style>
</head>

<body>

    <div class="apk-card text-center shadow-2xl animate__animated animate__zoomIn">

        <div class="mb-4 animate__animated animate__bounceIn animate__delay-1s">
            <i class="fab fa-android android-icon"></i>
        </div>

        <h3 class="fw-bold mb-2">Presensi AI Android</h3>
        <p class="text-muted mb-4 px-2">
            Demi keamanan GPS, akses browser dinonaktifkan. Silakan gunakan aplikasi resmi.
        </p>

        <div class="mb-4 animate__animated animate__fadeInUp animate__delay-1s">
            <div class="step-box">
                <span class="step-num">1</span> <small class="text-secondary fw-bold">Klik Tombol Download di bawah.</small>
            </div>
            <div class="step-box">
                <span class="step-num">2</span> <small class="text-secondary">Izinkan instalasi dari <b>"Sumber Tidak Dikenal"</b> di pengaturan HP.</small>
            </div>
            <div class="step-box">
                <span class="step-num">3</span> <small class="text-secondary">Buka file APK dan instal.</small>
            </div>
        </div>

        <div class="mt-4">
            <a href="<?= base_url('download/presensinew.apk') ?>" class="btn btn-download w-100 mb-3 animate__animated animate__pulse animate__infinite animate__slow">
                <i class="fas fa-download me-2 fa-lg"></i> DOWNLOAD APK SEKARANG
            </a>
            <p class="x-small text-muted" style="font-size: 0.75rem;">Versi Terbaru 2.0 (Stabil)</p>
        </div>

        <hr class="my-4" style="opacity: 0.1;">

        <div class="d-flex align-items-center justify-content-center animate__animated animate__fadeIn animate__delay-2s">
            <i class="fas fa-shield-alt text-success me-2"></i>
            <span class="small text-muted fw-bold">Official App by IT Team</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>