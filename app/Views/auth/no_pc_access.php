<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Dibatasi - Presensi AI Polman</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-grad: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --danger-color: #ef4444;
        }

        body {
            background: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(124, 58, 237, 0.15) 0px, transparent 50%);
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 40px;
            padding: 3rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        }

        .icon-wrapper {
            position: relative;
            margin-bottom: 2rem;
            display: inline-block;
        }

        .desktop-icon {
            color: var(--danger-color);
            position: relative;
            z-index: 2;
        }

        /* Lingkaran Berdenyut */
        .pulse-animation {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100px;
            height: 100px;
            background: rgba(239, 68, 68, 0.2);
            border-radius: 50%;
            z-index: 1;
            animation: pulse-ring 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
        }

        @keyframes pulse-ring {
            0% { transform: translate(-50%, -50%) scale(0.33); }
            80%, 100% { opacity: 0; }
        }

        .warning-badge {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .device-info {
            background: white;
            border-radius: 20px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .device-info:hover {
            border-color: #4f46e5;
            transform: scale(1.02);
        }

        .btn-portal {
            background: var(--primary-grad);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
        }

        .btn-portal:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.4);
            color: white;
        }
    </style>
</head>
<body>

    <div class="glass-card text-center animate__animated animate__fadeInUp">
        
        <div class="mb-2">
            <span class="warning-badge animate__animated animate__fadeInDown animate__delay-1s">
                Security Policy
            </span>
        </div>

        <div class="icon-wrapper">
            <div class="pulse-animation"></div>
            <i class="fas fa-desktop fa-5x desktop-icon animate__animated animate__shakeX animate__infinite animate__slow"></i>
        </div>

        <h2 class="fw-bold mb-3">Akses Dibatasi!</h2>
        
        <p class="text-muted mb-4 px-3">
            Sistem presensi tidak diizinkan diakses melalui <b>PC atau Laptop</b>. Mohon gunakan perangkat mobile resmi Anda.
        </p>

        <div class="device-info mb-4 d-flex align-items-center justify-content-center gap-3 animate__animated animate__fadeInUp animate__delay-1s">
            <div class="text-start">
                <p class="mb-0 small fw-bold text-dark">Gunakan Smartphone:</p>
                <div class="d-flex gap-2 mt-1">
                    <span class="badge bg-success rounded-pill"><i class="fab fa-android me-1"></i> Android</span>
                    <span class="badge bg-dark rounded-pill"><i class="fab fa-apple me-1"></i> iPhone</span>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="<?= base_url('auth') ?>" class="btn btn-portal w-100">
                <i class="fas fa-arrow-left me-2"></i> Kembali ke Halaman Utama
            </a>
        </div>

        <p class="x-small text-secondary mt-4 mb-0" style="font-size: 0.7rem;">
            Dinas Komunikasi dan Informatika<br>Kabupaten Polewali Mandar
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>