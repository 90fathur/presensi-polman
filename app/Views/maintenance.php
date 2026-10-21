<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - Presensi Polman 2.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }
        .maintenance-card {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 600px;
            text-align: center;
        }
        .logo-polman {
            font-size: 3.5rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .status-badge {
            background: #fff3cd;
            color: #856404;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 1.5rem;
        }
        .gear-icon {
            color: #6c757d;
            animation: rotate 4s linear infinite;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .progress {
            height: 8px;
            border-radius: 10px;
            margin: 2rem 0;
        }
    </style>
</head>
<body>

    <div class="container d-flex justify-content-center">
        <div class="maintenance-card animate__animated animate__fadeInDown">
            <div class="logo-polman animate__animated animate__pulse animate__infinite">
                <i class="fas fa-fingerprint"></i>
            </div>
            
            <h2 class="fw-bold mb-2">Presensi Polman 2.0</h2>
            <div class="status-badge">
                <i class="fas fa-tools me-2"></i>Sistem Sedang Diperbarui
            </div>

            <p class="text-muted px-3">
                Halo Rekan-rekan! Kami sedang melakukan pemeliharaan rutin untuk meningkatkan performa dan fitur absensi. Mohon maaf atas ketidaknyamanannya.
            </p>

            <div class="my-4">
                <i class="fas fa-cog gear-icon fa-3x"></i>
                <i class="fas fa-cog gear-icon fa-2x ms-2" style="animation-direction: reverse;"></i>
            </div>

            <div class="progress shadow-sm">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" style="width: 75%"></div>
            </div>

            <p class="small text-secondary">
                Estimasi selesai: <strong>Segera</strong> <br>
                &copy; <?= date('Y') ?> IT Support Polman.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>